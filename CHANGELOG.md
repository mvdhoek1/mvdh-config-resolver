# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-11-17

### Changed

- **Breaking for PHP 8.0/8.1 users:** the minimum PHP version is now 8.2. PHP 8.0 and 8.1 are end-of-life, and the test tooling no longer installs on them.

### Fixed

- Before `useSetting()` is called, `get($field)` now returns that top-level field instead of the entire configuration. `has()` and `getOrFail()` now work there as well instead of always reporting a match.
- `getOrFail()` without a selected setting gives a clear message ("in the configuration") instead of an empty setting name.

### Added

- PHPStan at the highest level (`composer analyse`).
- `composer check` to run code style, static analysis and tests together.
- GitHub Actions CI: code style and static analysis, plus tests on PHP 8.2–8.5 and with the lowest dependency versions.
- `.gitattributes` so tests and tooling are not included in the installed package.
- Package keywords, author and support links in `composer.json`.
- README notes on shared resolver state and on keys that contain a dot.

## [1.0.0] - 2026-11-17

First release as a Composer package.

### Added

- Composer package `mvdh/config-resolver` with PSR-4 autoloading (PHP 8.0+).
- `has()` to tell a missing key apart from a key whose value is `null`.
- `getOrFail()` that throws when nothing is found.
- `SettingNotFoundException` (extends `InvalidArgumentException`), thrown by `useSetting()` and `getOrFail()`.
- Searching for `null` by passing it explicitly to `searchBy()`.
- Pest test suite.
- Laravel Pint code style with `composer format` and `composer format:check`.

### Changed

- **Breaking:** the class moved from the global namespace to `MVDH\ConfigResolver\ConfigResolver`.
- **Breaking:** the protected `handle*()` methods now return a `[found, value]` pair.
- `useSetting()` resets search parameters from a previous `searchBy()` call.
- With `returnKey()`, `searchBy()` only returns the searched field when it actually matches.
- After a successful search on a one-dimensional setting, `get()` returns other fields of that setting.
- Multi-dimensional settings are detected by all rows being arrays instead of the presence of key `0`.

### Fixed

- PHP warning caused by a no-op `use InvalidArgumentException` statement.
- Falsy settings (`[]`, `0`, `''`, `'0'`, `false`, `null`) returning the whole configuration.
- Field `"0"` being treated as no field.
- Key `0` being returned as `null` with `returnKey()`.
- `TypeError` when a setting path goes through a non-array value, or when calling `get()` on a scalar setting.
- Incorrect exception message and misleading examples in the README.

[Unreleased]: https://github.com/mvdhoek1/mvdh-config-resolver/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/mvdhoek1/mvdh-config-resolver/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/mvdhoek1/mvdh-config-resolver/releases/tag/v1.0.0
