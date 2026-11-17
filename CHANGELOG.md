# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/mvdhoek1/mvdh-config-resolver/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/mvdhoek1/mvdh-config-resolver/releases/tag/v1.0.0
