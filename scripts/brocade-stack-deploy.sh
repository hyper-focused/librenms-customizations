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

# Enable debug output
set -x
set -e

LIBRENMS_ROOT="${LIBRENMS_ROOT:-/opt/librenms}"
BACKUP_DIR="${BACKUP_DIR:-$LIBRENMS_ROOT/librenms-backups}"
REPO_URL="https://github.com/hyper-focused/librenms-customizations.git"
BRANCH="${BRANCH:-main}"
CLONE_DIR="/tmp/librenms-customizations"

# Paths we overlay (relative to LibreNMS root)
PATHS=(
  "LibreNMS/OS/BrocadeStack.php"
  "includes/discovery/brocade-stack.inc.php"
  "includes/polling/brocade-stack.inc.php"
  "resources/definitions/os_detection/brocade-stack.yaml"
  "resources/definitions/os_discovery/brocade-stack.yaml"
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
)

GITIGNORE_MARKER="# librenms-customizations overlay (ignore so upstream git pull does not overwrite)"

if [ ! -d "$LIBRENMS_ROOT" ]; then
  echo "Error: LIBRENMS_ROOT=$LIBRENMS_ROOT not found."
  exit 1
fi

echo "LibreNMS root: $LIBRENMS_ROOT"
echo "Backup dir:    $BACKUP_DIR"
echo "Repo:          $REPO_URL ($BRANCH)"
echo "Clone dir:     $CLONE_DIR"
echo ""

# 1. Create backup dir and ensure librenms can write
echo "Creating backup directory..."
mkdir -p "$BACKUP_DIR"
chown librenms:librenms "$BACKUP_DIR" 2>/dev/null || true

# 2. Backup existing files (run as librenms)
echo "Backing up existing files..."
for p in "${PATHS[@]}"; do
  if [ -f "$LIBRENMS_ROOT/$p" ]; then
    sudo -u librenms mkdir -p "$BACKUP_DIR/$(dirname "$p")"
    sudo -u librenms cp -a "$LIBRENMS_ROOT/$p" "$BACKUP_DIR/$p"
    echo "  backed up: $p"
  else
    echo "  skip (not found): $p"
  fi
done

# 2.5. Remove orphan files (previously installed but no longer needed)
echo "Removing orphan/obsolete files..."
for p in "${ORPHAN_PATHS[@]}"; do
  if [ -f "$LIBRENMS_ROOT/$p" ]; then
    echo "  removing orphan: $p"
    rm -f "$LIBRENMS_ROOT/$p"
  else
    echo "  skip (not found): $p"
  fi
done

# 3. Clone repo (as librenms)
echo "Cloning $REPO_URL (branch: $BRANCH)..."
if ! sudo -u librenms bash -c "rm -rf $CLONE_DIR && git clone --depth 1 -b $BRANCH $REPO_URL $CLONE_DIR"; then
  echo "ERROR: Failed to clone repository. Check network connectivity and repository URL."
  echo "REPO_URL: $REPO_URL"
  echo "BRANCH: $BRANCH"
  echo "CLONE_DIR: $CLONE_DIR"
  exit 1
fi

# Verify clone was successful
if [ ! -d "$CLONE_DIR/.git" ]; then
  echo "ERROR: Clone directory does not exist or is not a git repository: $CLONE_DIR"
  exit 1
fi

echo "Repository cloned successfully to $CLONE_DIR"

# Verify we have the latest version
cd "$CLONE_DIR"
LATEST_COMMIT=$(git rev-parse HEAD)
echo "Latest commit: $LATEST_COMMIT"
cd - > /dev/null

# 4. Copy files into LibreNMS (as librenms)
echo "Copying overlay files..."
echo "Source directory: $CLONE_DIR"
echo "Destination directory: $LIBRENMS_ROOT"
echo ""

for p in "${PATHS[@]}"; do
  src="$CLONE_DIR/$p"
  dest="$LIBRENMS_ROOT/$p"

  if [ -f "$src" ]; then
    echo "  copying: $p"
    echo "    from: $src"
    echo "    to: $dest"

    # Create destination directory if it doesn't exist
    sudo -u librenms mkdir -p "$(dirname "$dest")"

    # Copy the file
    if sudo -u librenms cp "$src" "$dest"; then
      echo "    ✅ installed: $p"
    else
      echo "    ❌ ERROR: Failed to copy $p"
      exit 1
    fi
  else
    echo "  ⚠️  skip (not found in repo): $p"
    echo "    expected at: $src"
  fi
  echo ""
done

echo "File copying complete."

# 4.5. Verify installed files
echo ""
echo "Verifying installed files..."
for p in "${PATHS[@]}"; do
  dest="$LIBRENMS_ROOT/$p"
  if [ -f "$dest" ]; then
    timestamp=$(stat -c '%Y' "$dest" 2>/dev/null || stat -f '%m' "$dest" 2>/dev/null || echo "unknown")
    echo "  ✅ verified: $p (timestamp: $timestamp)"
  else
    echo "  ❌ missing: $p"
  fi
done
echo ""

# 5. Update .gitignore so upstream git pull ignores our overlay (as librenms)
GITIGNORE="$LIBRENMS_ROOT/.gitignore"
if sudo -u librenms grep -qF "$GITIGNORE_MARKER" "$GITIGNORE" 2>/dev/null; then
  echo ".gitignore already contains overlay marker; skipping."
else
  echo "Appending overlay paths to .gitignore..."
  sudo -u librenms bash -c "cat >> '$GITIGNORE' << 'GITIGNORE_EOF'

$GITIGNORE_MARKER
LibreNMS/OS/BrocadeStack.php
includes/discovery/brocade-stack.inc.php
includes/polling/brocade-stack.inc.php
resources/definitions/os_detection/brocade-stack.yaml
resources/definitions/os_discovery/brocade-stack.yaml
docs/brocade-stack-implementation.md
GITIGNORE_EOF"
  echo "  done."
fi

# 6. Clear ALL caches aggressively (critical for YAML definitions)
echo "Clearing LibreNMS caches..."
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

echo "Caches cleared aggressively"

# 7. Check for migrations (no migrations needed for this OS)
echo "Checking for database migrations..."
echo "Note: This OS uses device_attribs only - no custom tables or migrations required"
echo "✅ Skipping database migrations (not needed for brocade-stack OS)"

echo ""
echo "🎉 DEPLOYMENT COMPLETE!"
echo ""
echo "📁 Summary:"
echo "  LibreNMS root: $LIBRENMS_ROOT"
echo "  Backups saved: $BACKUP_DIR"
echo "  Repository: $REPO_URL ($BRANCH)"
echo ""
echo "🔧 Next steps:"
echo "  1. Restart PHP-FPM and web server:"
echo "     sudo systemctl restart php*-fpm nginx"
echo ""
echo "  2. Test discovery on your devices:"
echo "     sudo -u librenms bash -c 'cd $LIBRENMS_ROOT && php discovery.php -h <device_id>'"
echo ""
echo "  3. Check LibreNMS web interface for new sensors"
echo ""
echo "📝 Notes:"
echo "  - Git pulls from upstream LibreNMS will ignore overlay files (.gitignore updated)"
echo "  - All caches have been cleared aggressively"
echo "  - No database migrations required (uses device_attribs only)"
echo "  - Orphan files have been removed"

echo ""
echo "🔍 Verification Steps:"
echo "  Run these commands to verify the deployment:"
echo ""
echo "  # Check that brocade-stack OS is available:"
echo "  sudo -u librenms bash -c 'cd $LIBRENMS_ROOT && php -r \"echo \\\"Available OS: \\\"; \$os = include \\\"includes/definitions/os.php\\\"; echo isset(\$os['brocade-stack']) ? 'YES' : 'NO';\"'"
echo ""
echo "  # Test discovery on a device (replace DEVICE_ID):"
echo "  sudo -u librenms bash -c 'cd $LIBRENMS_ROOT && php discovery.php -h DEVICE_ID -d' | head -20"