# LibreNMS Compliant Directory Structure

**Last Updated**: January 17, 2026  
**Status**: ✅ Restructured to match official LibreNMS

---

## 📁 Official LibreNMS Structure (Our Implementation)

```
/workspace/  (Project Repository)
│
├── LibreNMS/                           ← OS Classes
│   └── OS/
│       └── Ironware.php                ✅ Enhanced with stack topology
│
├── app/                                ← Laravel Models
│   └── Models/
│       ├── IronwareStackTopology.php  ✅ Stack topology model
│       └── IronwareStackMember.php    ✅ Stack member model
│
├── database/                           ← Laravel Migrations
│   └── migrations/
│       └── 2026_01_17_000001_add_ironware_stack_tables.php ✅
│
├── resources/                          ← Resources
│   ├── definitions/
│   │   ├── os_detection/
│   │   │   └── ironware-enhanced.yaml  ✅ Enhanced detection
│   │   └── os_discovery/
│   │       └── (enhance existing ironware.yaml)
│   └── views/
│       └── device/
│           └── tabs/
│               └── ironware-stack.blade.php ✅ Stack UI
│
├── tests/                              ← Test Data
│   ├── snmpsim/
│   │   ├── ironware_fcx648.snmprec    ✅ FCX648 test data
│   │   └── ironware_icx6450.snmprec   ✅ ICX6450 test data
│   └── data/
│       ├── ironware_fcx648.json        ⏳ (generate with save-test-data.php)
│       └── ironware_icx6450.json       ⏳ (generate with save-test-data.php)
│
├── docs/                               ← Project Documentation
│   ├── SNMP_REFERENCE.md
│   ├── PLATFORM_DIFFERENCES.md
│   ├── REAL_DEVICE_DATA.md
│   ├── IMPLEMENTATION.md
│   └── MIB_ANALYSIS.md
│
├── examples/                           ← Examples
│   ├── ICX_EXAMPLES.md
│   └── README.md
│
├── mibs/                               ← MIB Files
│   ├── foundry/
│   └── brocade/
│
├── librenms-patches/                   ← Integration Patches
│   ├── 01-ironware-detection-enhancement.patch
│   └── 02-ironware-stack-topology.patch
│
└── [Project Documentation Files]
    ├── PROJECT_PLAN.md
    ├── PROJECT_COMPLETE.md
    ├── README.md
    ├── CHANGELOG.md
    ├── TODO.md
    └── ... (20+ more)
```

---

## 📋 File Mapping - Old vs New Structure

### OS Detection Files:

| Old Location (Non-Compliant) | New Location (Compliant) | Status |
|------------------------------|-------------------------|---------|
| `includes/definitions/foundry-fcx.yaml` | `resources/definitions/os_detection/ironware-enhanced.yaml` | ✅ Created |
| `includes/definitions/brocade-icx*.yaml` | _(merge into ironware.yaml)_ | ✅ Noted |

### OS Discovery Files:

| Old Location | New Location | Status |
|-------------|--------------|---------|
| `includes/discovery/os/brocade-ironware.inc.php` | `LibreNMS/OS/Ironware.php` | ✅ Created |

### Models:

| Old Location | New Location | Status |
|-------------|--------------|---------|
| _(not created)_ | `app/Models/IronwareStackTopology.php` | ✅ Created |
| _(not created)_ | `app/Models/IronwareStackMember.php` | ✅ Created |

### Migrations:

| Old Location | New Location | Status |
|-------------|--------------|---------|
| `sql-schema/migrations/*.sql` | `database/migrations/2026_01_17_000001_add_ironware_stack_tables.php` | ✅ Created |

### Views:

| Old Location | New Location | Status |
|-------------|--------------|---------|
| _(not created)_ | `resources/views/device/tabs/ironware-stack.blade.php` | ✅ Created |

### Test Data:

| Old Location | New Location | Status |
|-------------|--------------|---------|
| `tests/data/*.json` | `tests/data/ironware_*.json` | ⏳ Ready |
| _(not created)_ | `tests/snmpsim/ironware_fcx648.snmprec` | ✅ Created |
| _(not created)_ | `tests/snmpsim/ironware_icx6450.snmprec` | ✅ Created |

---

## 🎯 Files Ready for LibreNMS Integration

### To Copy to LibreNMS Repository:

```bash
# In LibreNMS repository:

# 1. Enhanced OS class (merge with existing)
cp /workspace/LibreNMS/OS/Ironware.php LibreNMS/OS/Ironware.php

# 2. Eloquent Models (new files)
cp /workspace/app/Models/IronwareStackTopology.php app/Models/
cp /workspace/app/Models/IronwareStackMember.php app/Models/

# 3. Database Migration (new file)
cp /workspace/database/migrations/2026_01_17_000001_add_ironware_stack_tables.php database/migrations/

# 4. Blade View (new file)
cp /workspace/resources/views/device/tabs/ironware-stack.blade.php resources/views/device/tabs/

# 5. Enhanced Detection (merge with existing)
# Merge content from resources/definitions/os_detection/ironware-enhanced.yaml
# into existing: resources/definitions/os_detection/ironware.yaml

# 6. Test Data (new files)
cp /workspace/tests/snmpsim/ironware_fcx648.snmprec tests/snmpsim/
cp /workspace/tests/snmpsim/ironware_icx6450.snmprec tests/snmpsim/

# 7. Generate json test data
./scripts/save-test-data.php -o ironware -v fcx648
./scripts/save-test-data.php -o ironware -v icx6450
```

---

## 🔧 Implementation Workflow

### Phase 1: Detection Enhancement

**Files to Modify**:
1. `resources/definitions/os_detection/ironware.yaml` - Add sysObjectID patterns

**Files to Add**:
2. `tests/snmpsim/ironware_fcx648.snmprec` - Test data
3. `tests/snmpsim/ironware_icx6450.snmprec` - Test data
4. `tests/data/ironware_fcx648.json` - Generated
5. `tests/data/ironware_icx6450.json` - Generated

**Testing**:
```bash
lnms dev:check unit -o ironware
```

### Phase 2: Stack Topology

**Files to Modify**:
1. `LibreNMS/OS/Ironware.php` - Add discoverStackTopology()

**Files to Add**:
2. `app/Models/IronwareStackTopology.php` - New model
3. `app/Models/IronwareStackMember.php` - New model
4. `database/migrations/2026_01_17_000001_add_ironware_stack_tables.php` - New migration

**Testing**:
```bash
php artisan migrate
lnms device:discover -vv HOSTNAME
lnms dev:check unit --db --snmpsim -o ironware
```

### Phase 3: Web Interface

**Files to Add**:
1. `resources/views/device/tabs/ironware-stack.blade.php` - New view
2. `app/Http/Controllers/Device/IronwareStackController.php` - New controller (to create)
3. `routes/web.php` - Add route (to create)

---

## 📊 Structure Compliance Status

### ✅ Compliant Files Created:

| File | Location | LibreNMS Standard | Status |
|------|----------|------------------|---------|
| OS Class | `LibreNMS/OS/Ironware.php` | ✅ Correct | ✅ Ready |
| Model 1 | `app/Models/IronwareStackTopology.php` | ✅ Correct | ✅ Ready |
| Model 2 | `app/Models/IronwareStackMember.php` | ✅ Correct | ✅ Ready |
| Migration | `database/migrations/2026_01_17_*.php` | ✅ Correct | ✅ Ready |
| View | `resources/views/device/tabs/*.blade.php` | ✅ Correct | ✅ Ready |
| Detection | `resources/definitions/os_detection/*.yaml` | ✅ Correct | ✅ Ready |
| Test Data | `tests/snmpsim/*.snmprec` | ✅ Correct | ✅ Ready |

### ⏳ To Be Generated:

| File | Command | Status |
|------|---------|---------|
| `tests/data/ironware_fcx648.json` | `./scripts/save-test-data.php -o ironware -v fcx648` | ⏳ Pending |
| `tests/data/ironware_icx6450.json` | `./scripts/save-test-data.php -o ironware -v icx6450` | ⏳ Pending |

---

## 🎯 Directory Structure Validation

### LibreNMS Standard Structure (Verified):

```
/opt/librenms/
├── LibreNMS/
│   └── OS/
│       ├── Ironware.php          ✅ Our enhancement here
│       └── Shared/
│           └── Foundry.php       (existing base class)
│
├── app/
│   └── Models/
│       ├── IronwareStackTopology.php  ✅ Our model here
│       └── IronwareStackMember.php    ✅ Our model here
│
├── database/
│   └── migrations/
│       └── 2026_01_17_*.php      ✅ Our migration here
│
├── resources/
│   ├── definitions/
│   │   ├── os_detection/
│   │   │   └── ironware.yaml     ✅ Enhance this
│   │   └── os_discovery/
│   │       └── ironware.yaml     (existing, keep as-is)
│   └── views/
│       └── device/
│           └── tabs/
│               └── ironware-stack.blade.php ✅ Our view here
│
└── tests/
    ├── snmpsim/
    │   ├── ironware_fcx648.snmprec    ✅ Our test data
    │   └── ironware_icx6450.snmprec   ✅ Our test data
    └── data/
        ├── ironware_fcx648.json        ⏳ Generate
        └── ironware_icx6450.json       ⏳ Generate
```

**Compliance**: ✅ **100%** - All files in correct locations!

---

## ✅ What Changed

### Old Structure (Non-Compliant):
```
librenms-os-discovery/
├── includes/definitions/
├── includes/discovery/os/
├── tests/unit/
└── tests/mocks/
```

### New Structure (Compliant):
```
LibreNMS/OS/
app/Models/
database/migrations/
resources/definitions/os_detection/
resources/views/device/tabs/
tests/snmpsim/
tests/data/
```

**Result**: ✅ Matches official LibreNMS structure exactly!

---

## 📦 Ready for Integration

### All Files Match LibreNMS Conventions:

1. ✅ **LibreNMS/OS/Ironware.php** - Proper namespace, extends Foundry
2. ✅ **app/Models/** - Laravel Eloquent models
3. ✅ **database/migrations/** - Laravel migration format
4. ✅ **resources/definitions/os_detection/** - Detection YAML
5. ✅ **resources/views/device/tabs/** - Blade template
6. ✅ **tests/snmpsim/** - snmprec test format
7. ✅ **tests/data/** - json dumps (to generate)

**Status**: ✅ **Directory structure 100% compliant!**

---

## 🚀 Next Steps

1. Fork LibreNMS repository
2. Copy files to appropriate locations
3. Generate json test data
4. Run tests
5. Submit PR

**All files are now in LibreNMS-standard locations!** ✅
