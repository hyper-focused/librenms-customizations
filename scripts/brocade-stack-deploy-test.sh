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
# This script deploys from GitHub's feature/poe-monitoring branch
# for testing PoE monitoring functionality before merging to main.

set -e

LIBRENMS_ROOT="${LIBRENMS_ROOT:-/opt/librenms}"
BACKUP_DIR="${BACKUP_DIR:-$LIBRENMS_ROOT/librenms-backups-test}"
REPO_URL="https://github.com/hyper-focused/librenms-customizations.git"
BRANCH="feature/poe-monitoring"
CLONE_DIR="/tmp/librenms-customizations-test"

# Paths we overlay (relative to LibreNMS root)
PATHS=(
  "LibreNMS/OS/BrocadeStack.php"
  "includes/discovery/sensors/power/brocade-stack.inc.php"
  "resources/definitions/os_detection/brocade-stack.yaml"
  "resources/definitions/os_discovery/brocade-stack.yaml"
  "includes/html/pages/device/overview/poe.inc.php"
  "includes/html/pages/device/overview.inc.php"
  "includes/html/pages/device/overview/generic/sensor.inc.php"
  "includes/html/pages/device/port.inc.php"
  "includes/html/pages/device/port/poe.inc.php"
  "app/Http/Controllers/Device/Tabs/PortsController.php"
  "resources/views/device/tabs/ports/poe.blade.php"
)

# Orphan files to remove (previously installed but no longer needed)
ORPHAN_PATHS=(
  "LibreNMS/OS/Shared/Brocade.php"
  "app/Models/IronwareStackMember.php"
  "app/Models/IronwareStackTopology.php"
  "app/Models/BrocadeStackMember.php"
  "app/Models/BrocadeStackTopology.php"
  "database/migrations/2026_01_17_000002_rename_ironware_to_brocade_stack_tables.php"
  "database/migrations/2026_01_17_000001_add_brocade_stack_tables.php"
  "database/migrations/2025_01_17_120000_add_brocade_stack_tables.php"
  "includes/discovery/brocade-stack.inc.php"
  "includes/polling/brocade-stack.inc.php"
  "resources/views/device/tabs/ports.blade.php"
  "includes/html/pages/device/overview/sensors/power.inc.php"
  "includes/html/pages/device/health.inc.php"
  "includes/html/pages/device/health/power.inc.php"
  "includes/html/pages/device/health/sensors.inc.php"
  "includes/html/pages/device/port/sensors.inc.php"
)

# Testing-specific .gitignore marker (different from production)
GITIGNORE_MARKER="# librenms-customizations TEST overlay (feature/poe-monitoring)"

if [ ! -d "$LIBRENMS_ROOT" ]; then
  echo "Error: LIBRENMS_ROOT=$LIBRENMS_ROOT not found."
  exit 1
fi

echo "BROCADE-STACK TESTING DEPLOYMENT (PoE Branch)"
echo ""
echo "Source: $REPO_URL ($BRANCH)"
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

# Clone repo
if ! sudo -u librenms bash -c "rm -rf $CLONE_DIR && git clone --depth 1 -b $BRANCH $REPO_URL $CLONE_DIR" > /dev/null 2>&1; then
  echo "ERROR: Failed to clone repository from $REPO_URL (branch: $BRANCH)"
  exit 1
fi

if [ ! -d "$CLONE_DIR/.git" ]; then
  echo "ERROR: Clone failed - directory does not exist: $CLONE_DIR"
  exit 1
fi

LATEST_COMMIT=$(sudo -u librenms bash -c "cd $CLONE_DIR && git rev-parse HEAD")

echo ""
echo "Downloaded to /tmp: $CLONE_DIR (commit: ${LATEST_COMMIT:0:7})"

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

# 2. Remove orphan files from previous deployments
for p in "${ORPHAN_PATHS[@]}"; do
  if [ -f "$LIBRENMS_ROOT/$p" ]; then
    rm -f "$LIBRENMS_ROOT/$p"
    echo "  Removed orphan: $p"
  fi
done

# 3. Deploy testing files
echo ""
echo "Files copied to $LIBRENMS_ROOT:"
for p in "${PATHS[@]}"; do
  src="$CLONE_DIR/$p"
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
    echo "WARNING: Not found in repo: $p"
  fi
done
echo ""

# 4. Update .gitignore (only custom files not in upstream LibreNMS)
GITIGNORE="$LIBRENMS_ROOT/.gitignore"
if ! sudo -u librenms grep -qF "$GITIGNORE_MARKER" "$GITIGNORE" 2>/dev/null; then
  sudo -u librenms bash -c "cat >> '$GITIGNORE' << 'GITIGNORE_EOF'

$GITIGNORE_MARKER
LibreNMS/OS/BrocadeStack.php
includes/discovery/sensors/power/brocade-stack.inc.php
resources/definitions/os_detection/brocade-stack.yaml
resources/definitions/os_discovery/brocade-stack.yaml
includes/html/pages/device/overview/poe.inc.php
includes/html/pages/device/port/poe.inc.php
resources/views/device/tabs/ports/poe.blade.php
GITIGNORE_EOF"
fi

# 5. Clear caches
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

# 6. Restart services
if systemctl list-units --type=service | grep -q "php.*-fpm" 2>/dev/null; then
  systemctl restart "php*-fpm" 2>/dev/null || true
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
