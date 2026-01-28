# Project Status

**Unified FastIron & ICX Stack Discovery** — single LibreNMS module for FastIron (FCX, FWS, FLS, etc.) and ICX.

## Current state

- **Phase**: Implementation; core in place, ready for runtime testing.
- **OS**: One OS `brocade-stack` (display: "FastIron / ICX Stack") for both platforms.
- **Code**: Single codebase — `BrocadeStack.php`, shared detection/discovery YAML, stack models and migrations.

## Done

- Unified OS detection (FastIron + ICX) in `resources/definitions/os_detection/brocade-stack.yaml`.
- Unified discovery (mempools, sensors, hardware, stack-aware logic) in `resources/definitions/os_discovery/brocade-stack.yaml`.
- Stack topology and member discovery (MIB + fallback when stack MIBs missing).
- Per-unit hardware/serial; CPU discovery; PSU/fan OIDs aligned with real SNMP data.
- Database schema and models for stack topology and members.
- Docs cleaned: deprecated/old status .md files removed; remaining docs consolidated (see [docs/README.md](docs/README.md)).

## Next steps

1. **Runtime testing** — Run discovery/polling on real FastIron and ICX devices (stacked and standalone).
2. **Adjust from feedback** — Fix OIDs or logic from test results.
3. **TurboIron** — If it shares the same MIBs/stack behavior, add to this module; else leave for routing/modular project.
4. **Upstream** — Prepare PR to LibreNMS when stable.

## Links

- [README.md](README.md) — Overview and quick links  
- [PROJECT_PLAN.md](PROJECT_PLAN.md) — Goals and technical plan  
- [docs/UNIFIED_PLATFORM_SCOPE.md](docs/UNIFIED_PLATFORM_SCOPE.md) — Scope vs future routing/modular project  
- [docs/IMPLEMENTATION.md](docs/IMPLEMENTATION.md) — Implementation notes  
- [TODO.md](TODO.md) — Task list  
