#!/bin/bash
# Deploy librenms-customizations to a live LibreNMS install.
# Run as root; all file operations run as user librenms (sudo -u librenms).
#
# Usage:
#   sudo ./brocade-stack-deploy.sh
#
# Optional env vars:
#   LIBRENMS_ROOT   LibreNMS install path (default: /opt/librenms)
#   BACKUP_DIR      Where to backup replaced files (default: $LIBRENMS_ROOT/librenms-backups)
#   BRANCH         Git branch to use (default: main)
#
# To use /root/librenms-backups, create and chown first:
#   sudo mkdir -p /root/librenms-backups && sudo chown librenms:librenms /root/librenms-backups
#   export BACKUP_DIR=/root/librenms-backups; sudo -E ./brocade-stack-deploy.sh

set -e

LIBRENMS_ROOT="${LIBRENMS_ROOT:-/opt/librenms}"
BACKUP_DIR="${BACKUP_DIR:-$LIBRENMS_ROOT/librenms-backups}"
REPO_URL="https://github.com/hyper-focused/librenms-customizations.git"
BRANCH="${BRANCH:-main}"
CLONE_DIR="/tmp/librenms-customizations"

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
  "docs/brocade-stack-implementation.md"
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

GITIGNORE_MARKER="# librenms-customizations overlay (ignore so upstream git pull does not overwrite)"
GITIGNORE_MARKER_TEST="# librenms-customizations TEST overlay (feature/poe-monitoring)"

if [ ! -d "$LIBRENMS_ROOT" ]; then
  echo "Error: LIBRENMS_ROOT=$LIBRENMS_ROOT not found."
  exit 1
fi

echo "Deploying brocade-stack from $BRANCH to $LIBRENMS_ROOT"
echo ""

# 0. Check for and clean up any testing deployment artifacts
GITIGNORE="$LIBRENMS_ROOT/.gitignore"
if sudo -u librenms grep -qF "$GITIGNORE_MARKER_TEST" "$GITIGNORE" 2>/dev/null; then
  echo "Detected testing deployment artifacts - cleaning up..."
  sudo -u librenms bash -c "
    sed -i.bak '/$GITIGNORE_MARKER_TEST/,+7d' '$GITIGNORE'
    rm -f '${GITIGNORE}.bak'
  "
  TEST_BACKUP_DIR="$LIBRENMS_ROOT/librenms-backups-test"
  if [ -d "$TEST_BACKUP_DIR" ]; then
    echo "Testing backup directory preserved at: $TEST_BACKUP_DIR"
  fi
  echo ""
fi

# 1. Create backup dir and backup existing files
mkdir -p "$BACKUP_DIR"
chown librenms:librenms "$BACKUP_DIR" 2>/dev/null || true

for p in "${PATHS[@]}"; do
  if [ -f "$LIBRENMS_ROOT/$p" ]; then
    sudo -u librenms mkdir -p "$BACKUP_DIR/$(dirname "$p")"
    sudo -u librenms cp -a "$LIBRENMS_ROOT/$p" "$BACKUP_DIR/$p" 2>/dev/null || true
  fi
done

# 2. Remove orphan files
for p in "${ORPHAN_PATHS[@]}"; do
  if [ -f "$LIBRENMS_ROOT/$p" ]; then
    rm -f "$LIBRENMS_ROOT/$p"
  fi
done

# 3. Clone repo
if ! sudo -u librenms bash -c "rm -rf $CLONE_DIR && git clone --depth 1 -b $BRANCH $REPO_URL $CLONE_DIR" > /dev/null 2>&1; then
  echo "ERROR: Failed to clone repository from $REPO_URL (branch: $BRANCH)"
  exit 1
fi

if [ ! -d "$CLONE_DIR/.git" ]; then
  echo "ERROR: Clone failed - directory does not exist: $CLONE_DIR"
  exit 1
fi

cd "$CLONE_DIR"
LATEST_COMMIT=$(git rev-parse HEAD)
cd - > /dev/null

echo "Downloaded to /tmp: $CLONE_DIR (commit: ${LATEST_COMMIT:0:7})"
echo ""
echo "Files copied to $LIBRENMS_ROOT:"

# 4. Copy files into LibreNMS
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

# 5. Update .gitignore
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
docs/brocade-stack-implementation.md
GITIGNORE_EOF"
fi

# 6. Clear caches
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

# 7. Database migrations
echo "Database: No migrations required (uses device_attribs only)"

echo ""
echo "DEPLOYMENT COMPLETE"
echo ""
echo "Next steps:"
echo "  1. Restart services: sudo systemctl restart php*-fpm nginx"
echo "  2. Test discovery: sudo -u librenms bash -c 'cd $LIBRENMS_ROOT && php discovery.php -h <device_id>'"
echo ""
echo "Backups saved to: $BACKUP_DIR"