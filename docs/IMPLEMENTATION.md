# Implementation Notes

Unified FastIron + ICX stack discovery for LibreNMS. One OS (`brocade-stack`) and one codebase.

## Layout

| Path | Purpose |
|------|--------|
| `resources/definitions/os_detection/brocade-stack.yaml` | OS detection (sysDescr/sysObjectID) |
| `resources/definitions/os_discovery/brocade-stack.yaml` | Discovery config (mempools, sensors, hardware, etc.) |
| `LibreNMS/OS/BrocadeStack.php` | OS class: stack topology, per-unit inventory, CPU discovery |
| `LibreNMS/OS/Shared/Foundry.php` | Shared base (CPU discovery); project-specific |
| `app/Models/IronwareStackTopology.php` | Stack topology model |
| `app/Models/IronwareStackMember.php` | Stack member model |
| `database/migrations/` | Schema for stack tables |

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
- `LibreNMS/OS/Shared/Foundry.php` → LibreNMS `LibreNMS/OS/Shared/` (if not present)
- `resources/definitions/*` → LibreNMS `resources/definitions/`
- `app/Models/*` → LibreNMS `app/Models/`
- **Run migrations** so stack tables exist: `sudo -u librenms bash -c 'cd /opt/librenms && php artisan migrate --force'`

See [scripts/README.md](../scripts/README.md) for the deploy script.

## Deployment and live testing

1. **Run the migration** before or right after copying files. If the stack tables are missing you will see:
   - `Error discovering os module` and `Error discovering processors module`
   - `Table 'librenms.ironware_stack_topology' doesn't exist`
   - Fix: `sudo -u librenms bash -c 'cd /opt/librenms && php artisan migrate --force'`
2. If migration is not run, BrocadeStack now skips stack topology discovery and logs a warning instead of throwing; OS discovery and other modules still run.

## Troubleshooting discovery (FCX648 / IronWare 08.0.30u)

- **FOUNDRY-SN-SWITCH-GROUP-MIB::snStackingGlobalConfigState.0: Unknown Object Identifier** — Normal on some FCX/IronWare versions. The code falls back to standalone or alternative detection (Stack ports, sysName).
- **FOUNDRY-SN-AGENT-MIB::snChasUnitDescription.1 / snChasSerNum.0: Unknown Object Identifier** — Same: optional OIDs may be missing on older firmware; discovery continues with fallbacks.
- **Processors module error** — Usually the same missing-table issue; run the migration.

See [SNMP_REFERENCE.md](SNMP_REFERENCE.md) for OIDs and [UNIFIED_PLATFORM_SCOPE.md](UNIFIED_PLATFORM_SCOPE.md) for scope vs routing/modular project.
