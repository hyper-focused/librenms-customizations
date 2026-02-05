#!/bin/bash
# Revert testing deployment and restore production files
# Run as root; all file operations run as user librenms (sudo -u librenms).
#
# Usage:
#   sudo ./brocade-stack-deploy-test-revert.sh

set -e

LIBRENMS_ROOT="${LIBRENMS_ROOT:-/opt/librenms}"
BACKUP_DIR="${BACKUP_DIR:-$LIBRENMS_ROOT/librenms-backups-test}"
GITIGNORE_MARKER="# librenms-customizations TEST overlay (feature/poe-monitoring)"

if [ ! -d "$LIBRENMS_ROOT" ]; then
  echo "Error: LIBRENMS_ROOT=$LIBRENMS_ROOT not found."
  exit 1
fi

if [ ! -d "$BACKUP_DIR" ]; then
  echo "Error: Backup directory not found: $BACKUP_DIR"
  echo "Nothing to revert - testing deployment may not have been run."
  exit 1
fi

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║         REVERT TESTING DEPLOYMENT (Restore Production)        ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "📁 Backup source: $BACKUP_DIR"
echo "🎯 Restore to:    $LIBRENMS_ROOT"
echo ""
echo "⚠️  WARNING: This will restore production files from backup!"
echo "    All testing changes will be lost."
echo ""

# List files to be restored
echo "Files to be restored:"
find "$BACKUP_DIR" -type f | while read -r f; do
  rel="${f#$BACKUP_DIR/}"
  echo "  - $rel"
done
echo ""

read -p "Continue with REVERT? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
  echo "Revert cancelled."
  exit 0
fi

# 1. Restore backed up files
echo ""
echo "1️⃣  Restoring production files from backup..."
restored_count=0
find "$BACKUP_DIR" -type f | while read -r backup_file; do
  # Get relative path
  rel_path="${backup_file#$BACKUP_DIR/}"
  dest="$LIBRENMS_ROOT/$rel_path"

  echo "  📋 restoring: $rel_path"
  echo "     from: $backup_file"
  echo "     to:   $dest"

  # Create destination directory if needed
  sudo -u librenms mkdir -p "$(dirname "$dest")"

  # Restore the file
  if sudo -u librenms cp "$backup_file" "$dest"; then
    echo "     ✅ restored"
    restored_count=$((restored_count + 1))
  else
    echo "     ❌ ERROR: Failed to restore $rel_path"
    exit 1
  fi
  echo ""
done

echo "  ✅ Restored files from backup"

# 2. Remove TEST marker from .gitignore
GITIGNORE="$LIBRENMS_ROOT/.gitignore"
echo ""
echo "2️⃣  Cleaning up .gitignore..."
if sudo -u librenms grep -qF "$GITIGNORE_MARKER" "$GITIGNORE" 2>/dev/null; then
  echo "  📝 Removing TEST overlay marker from .gitignore..."

  # Create temp file and remove the TEST section
  sudo -u librenms bash -c "
    # Remove TEST marker and the 3 lines following it
    sed -i.bak '/$GITIGNORE_MARKER/,+3d' '$GITIGNORE'
    rm -f '${GITIGNORE}.bak'
  "

  echo "  ✅ .gitignore cleaned up"
else
  echo "  ℹ️  TEST marker not found in .gitignore (already clean)"
fi

# 3. Clear caches
echo ""
echo "3️⃣  Clearing LibreNMS caches..."
cd "$LIBRENMS_ROOT"

sudo -u librenms php artisan config:clear
sudo -u librenms php artisan cache:clear
sudo -u librenms php artisan view:clear
sudo -u librenms rm -rf bootstrap/cache/*
sudo -u librenms rm -f /tmp/*_librenms* 2>/dev/null || true
sudo -u librenms rm -f /tmp/librenms* 2>/dev/null || true
sudo -u librenms php -r 'if (function_exists("opcache_reset")) { opcache_reset(); }' 2>/dev/null || true
sudo -u librenms php -r 'if (function_exists("apcu_clear_cache")) { apcu_clear_cache(); }' 2>/dev/null || true
sudo -u librenms find . -name "*.cache" -delete 2>/dev/null || true
sudo -u librenms find . -name "*def*.php" -path "*/cache/*" -delete 2>/dev/null || true
sudo -u librenms php artisan config:cache

echo "  ✅ Caches cleared"

# 4. Restart services
echo ""
echo "4️⃣  Restarting services..."
if systemctl list-units --type=service | grep -q php.*-fpm; then
  systemctl restart php*-fpm
  echo "  ✅ PHP-FPM restarted"
fi

if systemctl is-active --quiet nginx; then
  systemctl restart nginx
  echo "  ✅ Nginx restarted"
elif systemctl is-active --quiet apache2; then
  systemctl restart apache2
  echo "  ✅ Apache restarted"
fi

# 5. Optional: Remove backup directory
echo ""
echo "5️⃣  Cleanup backup directory..."
read -p "Remove backup directory $BACKUP_DIR? (y/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
  rm -rf "$BACKUP_DIR"
  echo "  ✅ Backup directory removed"
else
  echo "  ℹ️  Backup directory preserved at: $BACKUP_DIR"
  echo "     You can manually remove it later with:"
  echo "     sudo rm -rf '$BACKUP_DIR'"
fi

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║          🎉 REVERT TO PRODUCTION COMPLETE! 🎉                 ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "✅ Production files have been restored"
echo "✅ Caches cleared and services restarted"
echo "✅ Testing changes removed"
echo ""
echo "📝 Next steps:"
echo "  1. Verify production deployment:"
echo "     sudo -u librenms bash -c 'cd $LIBRENMS_ROOT && php discovery.php -h <device> -d'"
echo ""
echo "  2. Check web interface for expected (non-PoE) functionality"
echo ""
echo "  3. If PoE testing was successful, merge feature branch:"
echo "     cd /Users/rob/Library/CloudStorage/Dropbox/scripts/librenms-customizations/librenms_mods"
echo "     git checkout main"
echo "     git merge feature/poe-monitoring"
echo "     git push"
echo ""
echo "  4. Then deploy from main branch:"
echo "     sudo ./brocade-stack-deploy.sh"
