# Scripts

## deploy-to-librenms.sh

Deploys this project’s overlay files into a live LibreNMS install. All commands that touch the LibreNMS tree run as user **librenms** (`sudo -u librenms`).

**On the test server (run as root):**

```bash
# Default: LibreNMS at /opt/librenms, backups under /opt/librenms/librenms-backups
sudo ./deploy-to-librenms.sh
```

**Use a different LibreNMS path:**

```bash
sudo LIBRENMS_ROOT=/path/to/librenms ./deploy-to-librenms.sh
```

**Use /root/librenms-backups for backups (one-time setup):**

```bash
sudo mkdir -p /root/librenms-backups
sudo chown librenms:librenms /root/librenms-backups
sudo BACKUP_DIR=/root/librenms-backups ./deploy-to-librenms.sh
```

**What it does:**

1. Backs up any existing files that will be replaced into `BACKUP_DIR` (paths preserved).
2. Clones `https://github.com/hyper-focused/librenms-customizations` (branch `main`) to `/tmp`.
3. Copies overlay files into the LibreNMS tree (OS class, Shared, definitions, models, migration).
4. Appends overlay paths to LibreNMS `.gitignore` so `git pull` from upstream LibreNMS does not overwrite them.
5. Runs `php artisan migrate --force` as user librenms for the stack tables.

All file and git operations in the LibreNMS directory are run as `librenms` via `sudo -u librenms`.
