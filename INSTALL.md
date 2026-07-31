# Installing explayouts_core

## Requirements

- Exponential Legacy / Exponential 6, PHP 8.1+
- `extension/explayouts` installed with its `explayouts_*` database schema (this extension only provides services on top of it)

## 1. Put the extension in place

```
extension/explayouts_core
```

## 2. Activate

Add it to the active extensions in `settings/override/site.ini.append.php`:

```ini
[ExtensionSettings]
ActiveExtensions[]=explayouts_core
```

(or `ActiveAccessExtensions[]` in a siteaccess `site.ini.append.php`).

The extension ships no INI settings of its own — it contains only the five service classes plus an `autoloads/explayouts_core_autoload.php` class map.

## 3. Regenerate autoloads and clear caches

```bash
php bin/php/ezpgenerateautoloads.php -e
php bin/php/ezcache.php --clear-all --purge --allow-root-user
```

## Sibling extensions

- Required: `explayouts` (value objects and database tables).
- Consumed by: `explayouts_ui` and `explayouts_ui_api` — activate `explayouts_core` before either of them.
