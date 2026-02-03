# Implementation Notes

Unified FastIron + ICX stack discovery for LibreNMS. One OS (`brocade-stack`) and one codebase.

## Layout

| Path | Purpose |
|------|--------|
| `LibreNMS/OS/BrocadeStack.php` | Main OS class for brocade-stack (includes CPU discovery) |
| `includes/discovery/brocade-stack.inc.php` | Additional discovery logic |
| `includes/polling/brocade-stack.inc.php` | Additional polling logic |
| `resources/definitions/os_detection/brocade-stack.yaml` | OS detection rules |
| `resources/definitions/os_discovery/brocade-stack.yaml` | Sensor and module configuration |
| `scripts/brocade-stack-deploy.sh` | Automated deployment script |
| `docs/brocade-stack-implementation.md` | This documentation |

## Detection

- **OS**: `brocade-stack` (display: "FastIron / ICX Stack").
- Matches: "Stacking System" or "IronWare" or "FastIron" in sysDescr; sysObjectID under Foundry (1991) or Brocade (1588) as in os_detection YAML.
- Covers FastIron (FCX, FWS, FLS, etc.) and ICX in one module.

## Discovery

- YAML drives mempools, sensors, hardware, version; PHP adds stack topology and members.
- Stack: when stack MIBs are missing (e.g. firmware 08.0.30u), fallback uses interface names (e.g. Stack1/1) and sysName.
- Hardware/serial: unit-indexed tables when stacked, scalar OIDs when standalone.

## MIBs

- FOUNDRY-SN-ROOT-MIB, FOUNDRY-SN-AGENT-MIB, FOUNDRY-SN-SWITCH-GROUP-MIB, FOUNDRY-SN-STACKING-MIB.
- MIBs live under `mibs/foundry/` (see `mibs/README.md`).

## Integration

Copy into LibreNMS:

- `LibreNMS/OS/BrocadeStack.php` → LibreNMS `LibreNMS/OS/`
- `includes/discovery/brocade-stack.inc.php` → LibreNMS `includes/discovery/`
- `includes/polling/brocade-stack.inc.php` → LibreNMS `includes/polling/`
- `resources/definitions/*` → LibreNMS `resources/definitions/`

**No database migrations required** - uses `device_attribs` for storage.

See `scripts/brocade-stack-deploy.sh` for automated deployment.

## Deployment and live testing

1. **Run the deployment script**: `sudo ./scripts/brocade-stack-deploy.sh`
   - Copies all files to correct locations
   - Clears caches automatically
   - No database migrations required
   - Fix: `sudo -u librenms bash -c 'cd /opt/librenms && php artisan migrate --force'`
2. If migration is not run, BrocadeStack now skips stack topology discovery and logs a warning instead of throwing; OS discovery and other modules still run.

## Entity-state and entity-physical

**entity-state** should remain **disabled** for `brocade-stack`. It depends on entity-physical (ENTITY-MIB::entPhysicalTable). We disable entity-physical because FCX/ICX typically do not implement ENTITY-MIB, so entity-state would have no entities to show and would add overhead with no benefit.

## Discovery optimizations

- **entity-physical disabled** for `brocade-stack`: FCX/ICX typically lack ENTITY-MIB::entPhysicalTable; leaving it enabled caused a failed bulkwalk every run. Discovery and poller both set `entity-physical: false`.
- **Reduced BrocadeStack logging**: Verbose `\Log::debug()` blocks (H1–H7, hypothesis-style) were removed from the hot path to cut log I/O and string building.
- **No duplicate SNMP in config-table path**: When stack data comes from the config table, topology and stack MAC are fetched once in `discoverStackViaAlternativeMethod()` and passed into `processConfigTableMembers()` instead of fetching again inside that method.

See [SNMP_REFERENCE.md](SNMP_REFERENCE.md) for OIDs.

## Troubleshooting discovery (FCX648 / IronWare 08.0.30u)

- **FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalConfigState.0: Unknown Object Identifier** — Normal on some FCX/IronWare versions. The code falls back to standalone or alternative detection (Stack ports, sysName).
- **FOUNDRY-SN-AGENT-MIB::snChasUnitDescription.1 / snChasSerNum.0: Unknown Object Identifier** — Same: optional OIDs may be missing on older firmware; discovery continues with fallbacks.
- **Processors module error** — Usually the same missing-table issue; run the migration.

See [SNMP_REFERENCE.md](SNMP_REFERENCE.md) for OIDs and [UNIFIED_PLATFORM_SCOPE.md](UNIFIED_PLATFORM_SCOPE.md) for scope vs routing/modular project.
