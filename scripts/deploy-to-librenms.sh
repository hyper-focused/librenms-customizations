#!/bin/bash
# Deploy librenms-customizations to a live LibreNMS install.
# Run as root; all file operations run as user librenms (sudo -u librenms).
#
# Usage:
#   sudo ./deploy-to-librenms.sh
#
# Optional env vars:
#   LIBRENMS_ROOT   LibreNMS install path (default: /opt/librenms)
#   BACKUP_DIR      Where to backup replaced files (default: $LIBRENMS_ROOT/librenms-backups)
#   BRANCH         Git branch to use (default: main)
#
# To use /root/librenms-backups, create and chown first:
#   sudo mkdir -p /root/librenms-backups && sudo chown librenms:librenms /root/librenms-backups
#   export BACKUP_DIR=/root/librenms-backups; sudo -E ./deploy-to-librenms.sh

set -e

LIBRENMS_ROOT="${LIBRENMS_ROOT:-/opt/librenms}"
BACKUP_DIR="${BACKUP_DIR:-$LIBRENMS_ROOT/librenms-backups}"
REPO_URL="https://github.com/hyper-focused/librenms-customizations.git"
BRANCH="${BRANCH:-main}"
CLONE_DIR="/tmp/librenms-customizations"

# Paths we overlay (relative to LibreNMS root)
PATHS=(
  "LibreNMS/OS/BrocadeStack.php"
  "LibreNMS/OS/Shared/Foundry.php"
  "resources/definitions/os_detection/brocade-stack.yaml"
  "resources/definitions/os_discovery/brocade-stack.yaml"
  "app/Models/IronwareStackMember.php"
  "app/Models/IronwareStackTopology.php"
  "database/migrations/2025_01_17_120000_add_ironware_stack_tables.php"
)

GITIGNORE_MARKER="# librenms-customizations overlay (ignore so upstream git pull does not overwrite)"

if [ ! -d "$LIBRENMS_ROOT" ]; then
  echo "Error: LIBRENMS_ROOT=$LIBRENMS_ROOT not found."
  exit 1
fi

echo "LibreNMS root: $LIBRENMS_ROOT"
echo "Backup dir:    $BACKUP_DIR"
echo "Repo:          $REPO_URL ($BRANCH)"
echo ""

# 1. Create backup dir and ensure librenms can write
mkdir -p "$BACKUP_DIR"
chown librenms:librenms "$BACKUP_DIR" 2>/dev/null || true

# 2. Backup existing files (run as librenms)
echo "Backing up existing files..."
for p in "${PATHS[@]}"; do
  if [ -f "$LIBRENMS_ROOT/$p" ]; then
    sudo -u librenms mkdir -p "$BACKUP_DIR/$(dirname "$p")"
    sudo -u librenms cp -a "$LIBRENMS_ROOT/$p" "$BACKUP_DIR/$p"
    echo "  backed up: $p"
  fi
done

# 3. Clone repo (as librenms)
echo "Cloning $REPO_URL..."
sudo -u librenms bash -c "rm -rf $CLONE_DIR && git clone --depth 1 -b $BRANCH $REPO_URL $CLONE_DIR"

# 4. Copy files into LibreNMS (as librenms)
echo "Copying overlay files..."
for p in "${PATHS[@]}"; do
  src="$CLONE_DIR/$p"
  if [ -f "$src" ]; then
    sudo -u librenms mkdir -p "$(dirname "$LIBRENMS_ROOT/$p")"
    sudo -u librenms cp "$src" "$LIBRENMS_ROOT/$p"
    echo "  installed: $p"
  else
    echo "  skip (not in repo): $p"
  fi
done

# 5. Update .gitignore so upstream git pull ignores our overlay (as librenms)
GITIGNORE="$LIBRENMS_ROOT/.gitignore"
if sudo -u librenms grep -qF "$GITIGNORE_MARKER" "$GITIGNORE" 2>/dev/null; then
  echo ".gitignore already contains overlay marker; skipping."
else
  echo "Appending overlay paths to .gitignore..."
  sudo -u librenms bash -c "cat >> '$GITIGNORE' << 'GITIGNORE_EOF'

$GITIGNORE_MARKER
LibreNMS/OS/BrocadeStack.php
LibreNMS/OS/Shared/Foundry.php
resources/definitions/os_detection/brocade-stack.yaml
resources/definitions/os_discovery/brocade-stack.yaml
app/Models/IronwareStackMember.php
app/Models/IronwareStackTopology.php
database/migrations/2025_01_17_120000_add_ironware_stack_tables.php
GITIGNORE_EOF"
  echo "  done."
fi

# 6. Run migrations (as librenms)
echo "Running database migrations..."
sudo -u librenms bash -c "cd $LIBRENMS_ROOT && php artisan migrate --force" 2>/dev/null || echo "  (migrate skipped or failed; run manually: sudo -u librenms bash -c 'cd $LIBRENMS_ROOT && php artisan migrate --force')"

echo ""
echo "Deploy complete. Backups are in $BACKUP_DIR"
echo "Git pulls from upstream LibreNMS will ignore the overlay paths in .gitignore"
