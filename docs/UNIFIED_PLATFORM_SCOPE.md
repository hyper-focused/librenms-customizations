# Unified Platform Scope

This document defines which platforms are covered by this single unified module and how it relates to a future routing/modular project.

---

## This Module: FastIron + ICX (Stackable / Fixed)

**Single unified OS**: `brocade-stack`  
**Display name**: FastIron / ICX Stack

One codebase handles all of the following under shared detection, discovery, and polling:

### FastIron (IronWare / FastIron OS)

- **FCX** — FCX624, FCX648, etc. (Foundry legacy stackable)
- **FWS** — FastIron Workgroup Switch
- **FLS** — FastIron Layer 2/3 Switch
- **Other FastIron stackable/fixed** — Same MIB set (FOUNDRY-SN-*), enterprise 1991

### ICX (FastIron OS)

- **ICX 6430 / 6450 / 6610 / 6650 / 7150 / 7250 / 7450 / 7650 / 7750** — All stackable ICX series  
- Same MIBs and logic as FastIron; enterprise 1991 (and optionally 1588)

### Optional: TurboIron

- If **TurboIron** shares the same MIBs and stacking behavior as FastIron/ICX, it can be included in this module.
- If it is closer to routing/modular platforms (NetIron, XMR, etc.), it will be omitted here and handled in the second project.

---

## Future Project: Routing / Modular (Out of Scope Here)

A **second project** will improve support for:

- **NetIron**
- **XMR**
- **MLXe**
- **CES/CER**
- **SuperIron**
- Other **routing-oriented** or **modular slot-based** platforms

**TurboIron** may be included there if it fits better with routing/modular than with stackable FastIron/ICX.

No work for that project is started from this repo; this module remains focused on **unified FastIron + ICX** only.

---

## Summary

| Scope            | Platforms                          | This repo        |
|-----------------|-------------------------------------|------------------|
| **This module** | FastIron (FCX, FWS, FLS, …) + ICX   | ✅ Single unified |
| **Optional**     | TurboIron (if synergies exist)      | ✅ Can add here   |
| **Future project** | NetIron, XMR, MLXe, CES/CER, SuperIron | ❌ Do not start here |

All OS definitions, discovery, and polling for FastIron and ICX are unified in the `brocade-stack` OS and `BrocadeStack` class.
