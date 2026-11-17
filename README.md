# Config Resolver

[![CI](https://github.com/mvdhoek1/mvdh-config-resolver/actions/workflows/ci.yml/badge.svg)](https://github.com/mvdhoek1/mvdh-config-resolver/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/mvdh/config-resolver.svg)](https://packagist.org/packages/mvdh/config-resolver)
[![PHP Version](https://img.shields.io/packagist/dependency-v/mvdh/config-resolver/php.svg)](https://packagist.org/packages/mvdh/config-resolver)
[![License](https://img.shields.io/packagist/l/mvdh/config-resolver.svg)](LICENSE)

Read, search and reverse-look-up values in nested configuration arrays, with an immutable API that is safe to share.

```php
use MVDH\ConfigResolver\Config;

$config = Config::from([
    'field_mapping' => ['userId' => 'UID', 'email' => 'EmailAddress'],
    'forms' => [
        ['id' => 'contact', 'recipient' => 'info@example.com'],
        ['id' => 'newsletter', 'recipient' => 'news@example.com'],
    ],
]);

$config->get('field_mapping.userId');                              // 'UID'
$config->in('field_mapping')->keyOf('EmailAddress');               // 'email'
$config->in('forms')->find('id', 'newsletter')?->get('recipient'); // 'news@example.com'
```

## Features

- Read nested values with dot notation, with an optional default.
- Scope to a part of the configuration with `in()`.
- Reverse lookup: find the key of a value with `keyOf()`.
- Find a row in a list of arrays with `find()` and `findOrFail()`.
- Tell a missing key apart from a `null` value with `has()`, or fail fast with `getOrFail()`.
- Immutable: every call returns a value or a new instance, so one call never affects another.
- Strict (`===`) comparisons, clear exceptions, no runtime dependencies.

## Installation

```bash
composer require mvdh/config-resolver
```

Requires PHP 8.2 or higher.

## Usage

### Creating a Config

```php
use MVDH\ConfigResolver\Config;

$config = Config::from([
    'person' => [
        'name' => 'Dave',
        'surname' => 'Rossi',
        'options' => [
            ['skill' => 'cute', 'since' => 'baby'],
            ['skill' => 'smart', 'since' => 'teenager'],
        ],
    ],
]);
```

### Reading Values

`get()` reads a key at the current level or a dot-notated path. Missing keys return `null`, or the default you pass:

```php
$config->get('person.name');              // 'Dave'
$config->get('person.options.1.skill');   // 'smart'
$config->get('person.age');               // null
$config->get('person.age', 30);           // 30
```

A key that exists with the value `null` returns `null`, not the default. Use `has()` to tell the two apart, or `getOrFail()` to throw when a key is missing:

```php
$config->has('person.name');        // true
$config->has('person.age');         // false
$config->getOrFail('person.age');   // throws SettingNotFoundException
```

`all()` returns the whole array of the current level.

### Keys That Contain a Dot

An exact key at the current level always wins; only when it does not exist is the key read as a path:

```php
$config = Config::from(['app.name' => 'flat', 'app' => ['name' => 'nested', 'version' => '1.0']]);

$config->get('app.name');    // 'flat' (exact key)
$config->get('app.version'); // '1.0'  (path)
```

### Scoping with `in()`

`in()` returns a new `Config` for a nested array. The original instance does not change:

```php
$person = $config->in('person');

$person->get('name');           // 'Dave'
$person->path();                // 'person'
$config->get('person.surname'); // still works: $config is unchanged
```

`in()` throws a `SettingNotFoundException` when the path does not exist or does not point to an array. Use `get()` for scalar values.

### Reverse Lookup with `keyOf()`

`keyOf()` returns the key whose value is exactly (`===`) the given value, or `null`:

```php
$mapping = Config::from(['userId' => 'UID', 'email' => 'EmailAddress']);

$mapping->keyOf('EmailAddress'); // 'email'
$mapping->keyOf('Unknown');      // null
```

### Finding Rows with `find()`

`find()` returns the first row (an array at the current level) where a field is exactly the given value, as a new `Config`, or `null`:

```php
$options = $config->in('person.options');

$smart = $options->find('skill', 'smart');
$smart?->get('since'); // 'teenager'
$smart?->path();       // 'person.options.1'

$options->find('skill', 'lazy');       // null
$options->findOrFail('skill', 'lazy'); // throws SettingNotFoundException
```

The field may be a dot-notated path inside the row, e.g. `find('address.city', 'Utrecht')`. Comparisons are strict, so you can also search for `false`, `0` or `null`.

### Sharing an Instance

A `Config` never changes after it has been created, so you can safely register one instance in a service container and use it everywhere:

```php
$config->in('settings.field_mapping')->keyOf('EmailAddress'); // 'email'
$config->in('settings.database')->get('user');                // 'root', regardless of the call above
```

## Error Handling

`in()`, `getOrFail()` and `findOrFail()` throw a `MVDH\ConfigResolver\SettingNotFoundException`, which extends `InvalidArgumentException`:

```php
use MVDH\ConfigResolver\SettingNotFoundException;

try {
    $config->in('person.unknown');
} catch (SettingNotFoundException $e) {
    echo $e->getMessage(); // 'The requested setting "person.unknown" is not configured. Available keys: person'
}
```

## Migrating from ConfigResolver

`ConfigResolver` is deprecated since 1.2.0 and will be removed in 2.0.0. It keeps state between calls, which makes results depend on earlier calls on the same instance. `Config` replaces it:

| `ConfigResolver` (deprecated) | `Config` |
|---|---|
| `new ConfigResolver($array)` | `Config::from($array)` |
| `->useSetting('a.b')` | `->in('a.b')` (returns a new instance) |
| `->get('field')` | `->get('field')` |
| `->get()` | `->all()` |
| `->has('field')` / `->getOrFail('field')` | `->has('field')` / `->getOrFail('field')` |
| `->returnKey()->get('value')` | `->keyOf('value')` |
| `->searchBy('field', 'value')` then `->get('other')` | `->find('field', 'value')?->get('other')` |
| `->returnKey()->searchBy('field', 'value')` on a list of rows | `->find('field', 'value') !== null` |
| `->searchBy('field', 'value')` on a single array | `->get('field') === 'value'` |
| `->useSetting('a.scalar')->get()` | `->get('a.scalar')` |

The documentation for `ConfigResolver` is in [docs/legacy-config-resolver.md](https://github.com/mvdhoek1/mvdh-config-resolver/blob/main/docs/legacy-config-resolver.md).

## Testing

The test suite uses [Pest](https://pestphp.com) and lives in `tests/Unit`.

```bash
composer install   # installs Pest as a dev dependency
composer test      # runs the suite (same as ./vendor/bin/pest)
```

Useful options:

```bash
./vendor/bin/pest --filter=getOrFail   # run matching tests only
./vendor/bin/pest --coverage           # coverage report (requires Xdebug or PCOV)
```

## Code Style

Code is formatted with [Laravel Pint](https://laravel.com/docs/pint) using the `laravel` preset (see `pint.json`):

```bash
composer format        # fix code style
composer format:check  # only report issues (e.g. in CI)
```

## Static Analysis

The code is checked with [PHPStan](https://phpstan.org) at the highest level (see `phpstan.neon.dist`):

```bash
composer analyse
```

## Running All Checks

```bash
composer check   # code style, static analysis and tests
```

GitHub Actions runs the same checks on every push and pull request, with the tests on PHP 8.2 to 8.5.

## License

GPL-3.0-or-later. See [LICENSE](LICENSE).
