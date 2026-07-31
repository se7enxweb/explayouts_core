# explayouts_core FAQ

## How does this differ from netgen/layouts-core?

`netgen/layouts-core` is a large Symfony package with a transaction-aware API layer, mappers, validators and Doctrine persistence. `explayouts_core` provides the same service vocabulary (layout, zone, block, collection, rule services) as five thin PHP classes that delegate to the `eZPersistentObject` value classes in `extension/explayouts`. There is no service container; you instantiate the classes directly.

## Which database tables does it own?

None. All `explayouts_*` tables belong to the `explayouts` extension; this extension is a pure service layer on top of them.

## Why do some load methods take a $status argument?

Layouts, zones and blocks exist as draft (`status = 1`) and published (`status = 2`) rows. `loadByLayout()` / `loadByZone()` / `listAll()` accept `null` to return rows regardless of status, which the admin UIs use to show drafts and published items together.

## Do the services check user permissions?

No. Permission checks happen in the calling module views (`explayouts_ui`, `explayouts_ui_api`) via the eZ module policy system (`explayouts/read`, `edit` functions). Do not expose these services to untrusted input without your own access checks.

## Can I use the services from a CLI script?

Yes — bootstrap an `eZScript` environment first (see `extension/explayouts/bin/php/layout_info.php` for a working example), then instantiate the services as usual.
