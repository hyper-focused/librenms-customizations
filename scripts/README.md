# Deployment Scripts

## deploy-to-librenms.sh

Automated deployment script for the BrocadeStack LibreNMS custom OS implementation.

### Features

- **Automated Backup**: Creates timestamped backups before deployment
- **File Deployment**: Copies all necessary files to LibreNMS directories
- **Database Migration**: Runs required database schema updates
- **Cache Management**: Clears LibreNMS caches for immediate effect
- **Verification**: Confirms successful deployment
- **User Flexibility**: Works when run as root or librenms user

### Usage

```bash
# IMPORTANT: Run from the repository root directory
cd /path/to/librenms-customizations

# As root (recommended)
sudo ./scripts/deploy-to-librenms.sh

# As librenms user
./scripts/deploy-to-librenms.sh
```

### What Gets Deployed

1. **OS Class**: `LibreNMS/OS/BrocadeStack.php` + `LibreNMS/OS/Shared/Foundry.php`
2. **YAML Configs**: Discovery and detection definitions
3. **Models**: `IronwareStackTopology.php` and `IronwareStackMember.php`
4. **MIBs**: Foundry MIB files for reference
5. **Database**: Migration for stack topology tables

### Configuration

Edit the script to set your LibreNMS installation path:

```bash
LIBRENMS_ROOT="/opt/librenms"  # Change if different
```

### Post-Deployment

After successful deployment:

```bash
# Test discovery
lnms device:discover <device_id>

# Test polling
lnms device:poll <device_id>

# Check web interface for:
# - Health → Memory graphs
# - Health → Processor graphs
# - Health → State (PSUs/Fans)
# - Device overview (stack topology)
# - Ports → Transceivers (if DOM supported)
```

### Rollback

If issues occur, the script creates backups in:
```
/opt/librenms/backups/YYYYMMDD_HHMMSS_brocade_stack_deploy/
```

Manually restore files from backup if needed.