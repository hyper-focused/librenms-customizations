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

# Enable debug output
set -x
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

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║       BROCADE-STACK TESTING DEPLOYMENT (PoE Branch)           ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "📦 Source:"
echo "  Local repo:    $LOCAL_REPO_DIR"
echo "  Branch:        $BRANCH"
echo "  Latest commit: $LATEST_COMMIT"
echo "  Commit msg:    $COMMIT_MSG"
echo ""
echo "🎯 Destination:"
echo "  LibreNMS root: $LIBRENMS_ROOT"
echo "  Backup dir:    $BACKUP_DIR"
echo ""
echo "⚠️  WARNING: This is a TESTING deployment from feature branch!"
echo "    Production files will be backed up and can be restored."
echo ""
read -p "Continue with TEST deployment? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
  echo "Deployment cancelled."
  exit 0
fi

# 1. Create backup dir and ensure librenms can write
echo ""
echo "1️⃣  Creating backup directory..."
mkdir -p "$BACKUP_DIR"
chown librenms:librenms "$BACKUP_DIR" 2>/dev/null || true

# 2. Backup existing files (run as librenms)
echo ""
echo "2️⃣  Backing up production files..."
for p in "${PATHS[@]}"; do
  if [ -f "$LIBRENMS_ROOT/$p" ]; then
    sudo -u librenms mkdir -p "$BACKUP_DIR/$(dirname "$p")"
    sudo -u librenms cp -a "$LIBRENMS_ROOT/$p" "$BACKUP_DIR/$p"
    echo "  ✅ backed up: $p"
  else
    echo "  ⚠️  skip (not found): $p"
  fi
done

# 3. Copy testing files into LibreNMS (as librenms)
echo ""
echo "3️⃣  Deploying TEST files from $BRANCH..."
for p in "${PATHS[@]}"; do
  src="$LOCAL_REPO_DIR/$p"
  dest="$LIBRENMS_ROOT/$p"

  if [ -f "$src" ]; then
    echo "  📋 copying: $p"
    echo "     from: $src"
    echo "     to:   $dest"

    # Create destination directory if it doesn't exist
    sudo -u librenms mkdir -p "$(dirname "$dest")"

    # Copy the file
    if sudo -u librenms cp "$src" "$dest"; then
      echo "     ✅ installed"
    else
      echo "     ❌ ERROR: Failed to copy $p"
      exit 1
    fi
  else
    echo "  ⚠️  skip (not found in local repo): $p"
    echo "     expected at: $src"
    exit 1
  fi
  echo ""
done

# 4. Update .gitignore for testing (different marker than production)
GITIGNORE="$LIBRENMS_ROOT/.gitignore"
echo ""
echo "4️⃣  Updating .gitignore for TEST files..."
if sudo -u librenms grep -qF "$GITIGNORE_MARKER" "$GITIGNORE" 2>/dev/null; then
  echo "  ℹ️  .gitignore already contains TEST marker"
else
  echo "  📝 Appending TEST overlay paths to .gitignore..."
  sudo -u librenms bash -c "cat >> '$GITIGNORE' << 'GITIGNORE_EOF'

$GITIGNORE_MARKER
LibreNMS/OS/BrocadeStack.php
resources/definitions/os_detection/brocade-stack.yaml
resources/definitions/os_discovery/brocade-stack.yaml
GITIGNORE_EOF"
  echo "  ✅ done"
fi

# 5. Clear ALL caches aggressively (critical for YAML definitions)
echo ""
echo "5️⃣  Clearing LibreNMS caches..."
cd "$LIBRENMS_ROOT"

# Clear Laravel caches
sudo -u librenms php artisan config:clear
sudo -u librenms php artisan cache:clear
sudo -u librenms php artisan view:clear

# Clear LibreNMS-specific caches
sudo -u librenms rm -rf bootstrap/cache/*
sudo -u librenms rm -f /tmp/*_librenms* 2>/dev/null || true
sudo -u librenms rm -f /tmp/librenms* 2>/dev/null || true

# Clear opcache and APCu if available
sudo -u librenms php -r 'if (function_exists("opcache_reset")) { opcache_reset(); echo "Opcache cleared\n"; }' 2>/dev/null || true
sudo -u librenms php -r 'if (function_exists("apcu_clear_cache")) { apcu_clear_cache(); echo "APCu cache cleared\n"; }' 2>/dev/null || true

# Force clear any cached YAML or definition files
sudo -u librenms find . -name "*.cache" -delete 2>/dev/null || true
sudo -u librenms find . -name "*def*.php" -path "*/cache/*" -delete 2>/dev/null || true

# Rebuild config cache
sudo -u librenms php artisan config:cache

echo "  ✅ Caches cleared aggressively"

# 6. Restart services
echo ""
echo "6️⃣  Restarting services..."
if systemctl list-units --type=service | grep -q php.*-fpm; then
  systemctl restart php*-fpm
  echo "  ✅ PHP-FPM restarted"
else
  echo "  ⚠️  PHP-FPM service not found - manual restart may be needed"
fi

if systemctl is-active --quiet nginx; then
  systemctl restart nginx
  echo "  ✅ Nginx restarted"
elif systemctl is-active --quiet apache2; then
  systemctl restart apache2
  echo "  ✅ Apache restarted"
else
  echo "  ⚠️  Web server not detected - manual restart may be needed"
fi

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║             🎉 TEST DEPLOYMENT COMPLETE! 🎉                    ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "📁 Summary:"
echo "  Source branch:  $BRANCH (commit: ${LATEST_COMMIT:0:7})"
echo "  LibreNMS root:  $LIBRENMS_ROOT"
echo "  Backups saved:  $BACKUP_DIR"
echo ""
echo "🧪 Testing Instructions:"
echo ""
echo "  1. Test discovery on PoE device (172.16.255.6):"
echo "     sudo -u librenms bash -c 'cd $LIBRENMS_ROOT && php discovery.php -h 172.16.255.6 -d -m sensors'"
echo ""
echo "  2. Check for PoE sensors:"
echo "     - Per-unit: Unit X PoE Capacity, Unit X PoE Consumption"
echo "     - Per-port: Port X PoE Limit, Port X PoE Consumption"
echo ""
echo "  3. Test discovery on non-PoE device:"
echo "     sudo -u librenms bash -c 'cd $LIBRENMS_ROOT && php discovery.php -h <non-poe-device> -d -m sensors'"
echo "     (Should gracefully skip PoE sensors)"
echo ""
echo "  4. Check LibreNMS web interface:"
echo "     - Overview page: PoE Power Budget section"
echo "     - Health tab: PoE graphs"
echo "     - Ports page: Per-port PoE data"
echo ""
echo "🔄 To Revert to Production:"
echo "  Run the revert script or manually restore:"
echo "  sudo ./brocade-stack-deploy-test-revert.sh"
echo ""
echo "  Or manually:"
echo "  for f in $BACKUP_DIR/*; do"
echo "    rel=\${f#$BACKUP_DIR/}"
echo "    sudo -u librenms cp \"\$f\" \"$LIBRENMS_ROOT/\$rel\""
echo "  done"
echo "  sudo -u librenms php artisan cache:clear"
echo "  sudo systemctl restart php*-fpm nginx"
echo ""
echo "📝 Notes:"
echo "  - This is a TEST deployment from feature branch"
echo "  - Production files are backed up in: $BACKUP_DIR"
echo "  - .gitignore updated with TEST marker"
echo "  - All caches cleared and services restarted"
echo ""
echo "⚠️  Remember to test thoroughly before merging to main!"
