# explayouts_core

Core domain service layer of the Exponential Layouts suite for Exponential Legacy / Exponential 6. It wraps the persistent value objects owned by the `explayouts` extension (`expLayoutsLayout`, `expLayoutsZone`, `expLayoutsBlock`, `expLayoutsCollection`, `expLayoutsRule`, ...) in small service classes with a create/load/update/delete API, so admin modules and the JSON API do not have to talk to `eZPersistentObject` directly.

Exponential Legacy port inspired by the `netgen/layouts-core` package. The services are intentionally thin: they use the eZ Publish legacy environment (database, cache, permissions) rather than the Doctrine/Symfony stack of the upstream package.

## Services

| Class | Purpose |
|-------|---------|
| `expLayoutsCoreLayoutService` | `load`, `loadDraft`, `loadPublished`, `listAll`, `create`, `update`, `publish`, `copy`, `delete`, `loadByIdentifier`, `listByType` |
| `expLayoutsCoreZoneService` | `load`, `loadByLayout`, `create`, `update`, `delete`, `countBlocks`, `setLinkedLayout` |
| `expLayoutsCoreBlockService` | `load`, `loadByZone`, `create`, `update`, `move`, `delete`, `setParameters`, `setCollection` |
| `expLayoutsCoreCollectionService` | `load`, `loadByBlock`, `create`, `update`, `delete`, `addItem`, `removeItem`, `loadItems`, `updateItem`, `setItems` |
| `expLayoutsCoreRuleService` | `load`, `listAll`, `create`, `update`, `setTargets`, `setConditions`, `copy`, `delete`, `listByLayout`, `enable`, `disable` |

## Consumers

- `extension/explayouts_ui/modules/explayouts_ui/` (`layout_list.php`, `rule_list.php`, `rule_edit.php`)
- `extension/explayouts_ui_api/` (JSON API dispatcher and module views)
- `extension/explayouts/bin/php/layout_info.php`

## Documentation

- [INSTALL.md](INSTALL.md) — activation
- [doc/USAGE.md](doc/USAGE.md) — PHP examples with real signatures, customization
- [doc/FAQ.md](doc/FAQ.md) — common questions
- [doc/TODO.md](doc/TODO.md) — known gaps
- [doc/SUPPORT.md](doc/SUPPORT.md) — how to get help
