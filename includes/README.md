# LibreNMS Integration Code

This directory contains code that will be integrated into LibreNMS for Foundry FCX switch support.

## Directory Structure

```
includes/
├── discovery/
│   └── os/
│       └── foundry.inc.php          # OS detection logic
├── definitions/
│   └── foundry.yaml                 # OS definition file
└── polling/
    └── foundry-stack.inc.php        # Stack polling module
```

## Files

### discovery/os/foundry.inc.php
OS detection logic that identifies Foundry FCX switches through:
- sysObjectID pattern matching
- sysDescr parsing
- Version extraction
- Hardware model detection
- Stack configuration detection

### definitions/foundry.yaml
YAML-based OS definition containing:
- OS metadata
- Default discovery modules
- Default polling modules
- MIB directory references
- Device-specific settings

### polling/foundry-stack.inc.php
Custom polling module for Foundry stack monitoring:
- Stack member status updates
- Hardware health checks
- Stack topology monitoring
- Performance metrics collection

## Integration with LibreNMS

To integrate these files into LibreNMS:

1. Copy files to corresponding LibreNMS directories:
   ```bash
   cp discovery/os/foundry.inc.php /opt/librenms/includes/discovery/os/
   cp definitions/foundry.yaml /opt/librenms/includes/definitions/
   cp polling/foundry-stack.inc.php /opt/librenms/includes/polling/
   ```

2. Install Foundry MIB files:
   ```bash
   cp ../mibs/foundry/* /opt/librenms/mibs/foundry/
   ```

3. Run database migrations:
   ```bash
   cd /opt/librenms
   php lnms migrate
   ```

4. Test discovery on a Foundry device:
   ```bash
   ./discovery.php -h device.example.com -d -m os
   ./discovery.php -h device.example.com -d -m foundry-stack
   ```

## Development

When developing these modules:

1. Follow LibreNMS coding standards (PSR-2)
2. Use LibreNMS helper functions
3. Add appropriate logging with d_echo() and c_echo()
4. Handle SNMP failures gracefully
5. Test with various switch configurations
6. Update documentation as needed

## Testing

Test files with PHPUnit:
```bash
./vendor/bin/phpunit tests/Feature/FoundryTest.php
```

Test with SNMP simulator:
```bash
snmpsimd.py --data-dir=../tests/data
./discovery.php -h 127.0.0.1:1024 -d
```

## References

- [LibreNMS OS Development](https://docs.librenms.org/Developing/os/)
- [Discovery Modules](https://docs.librenms.org/Developing/Discovery-Development/)
- [Polling Modules](https://docs.librenms.org/Developing/Poller-Development/)
