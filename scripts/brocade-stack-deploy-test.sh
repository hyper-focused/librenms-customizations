#!/bin/bash
# Deploy brocade-stack testing branch (feature/poe-monitoring) to LibreNMS
# Run as root; all file operations run as user librenms (sudo -u librenms).
#
# Usage:
#   sudo ./brocade-stack-deploy-test.sh
#
# Optional env vars:
#   LIBRENMS_ROOT   LibreNMS install path (default: /opt/librenms)
#   BACKUP_DIR      Where to backup replaced files (default: $LIBRENMS_ROOT/librenms-backups-test)
#
# This script deploys from the LOCAL repository's feature/poe-monitoring branch
# for testing PoE monitoring functionality before merging to main.

set -e

LIBRENMS_ROOT="${LIBRENMS_ROOT:-/opt/librenms}"
BACKUP_DIR="${BACKUP_DIR:-$LIBRENMS_ROOT/librenms-backups-test}"
LOCAL_REPO_DIR="/Users/rob/Library/CloudStorage/Dropbox/scripts/librenms-customizations/librenms_mods"
BRANCH="feature/poe-monitoring"

# Paths we overlay (relative to LibreNMS root)
PATHS=(
  "LibreNMS/OS/BrocadeStack.php"
  "resources/definitions/os_detection/brocade-stack.yaml"
  "resources/definitions/os_discovery/brocade-stack.yaml"
)

# Testing-specific .gitignore marker (different from production)
GITIGNORE_MARKER="# librenms-customizations TEST overlay (feature/poe-monitoring)"

if [ ! -d "$LIBRENMS_ROOT" ]; then
  echo "Error: LIBRENMS_ROOT=$LIBRENMS_ROOT not found."
  exit 1
fi

if [ ! -d "$LOCAL_REPO_DIR" ]; then
  echo "Error: LOCAL_REPO_DIR=$LOCAL_REPO_DIR not found."
  exit 1
fi

# Verify we're on the correct branch
cd "$LOCAL_REPO_DIR"
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "$BRANCH" ]; then
  echo "Error: Not on $BRANCH branch (currently on: $CURRENT_BRANCH)"
  echo "Please run: git checkout $BRANCH"
  exit 1
fi

# Check for uncommitted changes
if ! git diff-index --quiet HEAD --; then
  echo "Warning: You have uncommitted changes in the local repository."
  echo "The deployment will use the current working tree state."
  read -p "Continue? (y/N) " -n 1 -r
  echo
  if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 1
  fi
fi

LATEST_COMMIT=$(git rev-parse HEAD)
COMMIT_MSG=$(git log -1 --pretty=format:'%s')

cd - > /dev/null

echo "BROCADE-STACK TESTING DEPLOYMENT (PoE Branch)"
echo ""
echo "Source: $LOCAL_REPO_DIR ($BRANCH)"
echo "Commit: ${LATEST_COMMIT:0:7} - $COMMIT_MSG"
echo "Destination: $LIBRENMS_ROOT"
echo "Backup: $BACKUP_DIR"
echo ""
echo "WARNING: This is a TESTING deployment from feature branch."
echo ""
read -p "Continue with TEST deployment? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
  echo "Deployment cancelled."
  exit 0
fi

# 1. Backup production files
echo ""
mkdir -p "$BACKUP_DIR"
chown librenms:librenms "$BACKUP_DIR" 2>/dev/null || true

for p in "${PATHS[@]}"; do
  if [ -f "$LIBRENMS_ROOT/$p" ]; then
    sudo -u librenms mkdir -p "$BACKUP_DIR/$(dirname "$p")"
    sudo -u librenms cp -a "$LIBRENMS_ROOT/$p" "$BACKUP_DIR/$p"
  fi
done

# 2. Deploy testing files
echo "Files copied to $LIBRENMS_ROOT:"
for p in "${PATHS[@]}"; do
  src="$LOCAL_REPO_DIR/$p"
  dest="$LIBRENMS_ROOT/$p"

  if [ -f "$src" ]; then
    sudo -u librenms mkdir -p "$(dirname "$dest")"
    if sudo -u librenms cp "$src" "$dest"; then
      echo "  $p"
    else
      echo "ERROR: Failed to copy $p"
      exit 1
    fi
  else
    echo "ERROR: Not found in local repo: $p"
    exit 1
  fi
done
echo ""

# 3. Update .gitignore
GITIGNORE="$LIBRENMS_ROOT/.gitignore"
if ! sudo -u librenms grep -qF "$GITIGNORE_MARKER" "$GITIGNORE" 2>/dev/null; then
  sudo -u librenms bash -c "cat >> '$GITIGNORE' << 'GITIGNORE_EOF'

$GITIGNORE_MARKER
LibreNMS/OS/BrocadeStack.php
resources/definitions/os_detection/brocade-stack.yaml
resources/definitions/os_discovery/brocade-stack.yaml
GITIGNORE_EOF"
fi

# 4. Clear caches
cd "$LIBRENMS_ROOT"
sudo -u librenms php artisan config:clear > /dev/null 2>&1
sudo -u librenms php artisan cache:clear > /dev/null 2>&1
sudo -u librenms php artisan view:clear > /dev/null 2>&1
sudo -u librenms rm -rf bootstrap/cache/* 2>/dev/null || true
sudo -u librenms rm -f /tmp/*_librenms* 2>/dev/null || true
sudo -u librenms rm -f /tmp/librenms* 2>/dev/null || true
sudo -u librenms php -r 'if (function_exists("opcache_reset")) { opcache_reset(); }' 2>/dev/null || true
sudo -u librenms php -r 'if (function_exists("apcu_clear_cache")) { apcu_clear_cache(); }' 2>/dev/null || true
sudo -u librenms find . -name "*.cache" -delete 2>/dev/null || true
sudo -u librenms find . -name "*def*.php" -path "*/cache/*" -delete 2>/dev/null || true
sudo -u librenms php artisan config:cache > /dev/null 2>&1

# 5. Restart services
if systemctl list-units --type=service | grep -q php.*-fpm 2>/dev/null; then
  systemctl restart php*-fpm 2>/dev/null || true
fi

if systemctl is-active --quiet nginx 2>/dev/null; then
  systemctl restart nginx 2>/dev/null || true
elif systemctl is-active --quiet apache2 2>/dev/null; then
  systemctl restart apache2 2>/dev/null || true
fi

echo ""
echo "TEST DEPLOYMENT COMPLETE"
echo ""
echo "Testing PoE sensors:"
echo "  sudo -u librenms bash -c 'cd $LIBRENMS_ROOT && php discovery.php -h 172.16.255.6 -d -m sensors'"
echo ""
echo "Expected sensors:"
echo "  - Per-unit: Unit X PoE Capacity, Unit X PoE Consumption"
echo "  - Per-port: Port X PoE Limit, Port X PoE Consumption"
echo ""
echo "To revert to production:"
echo "  sudo ./brocade-stack-deploy-test-revert.sh"
echo ""
echo "Backups saved to: $BACKUP_DIR"
