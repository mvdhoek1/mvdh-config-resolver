<?php

/**
 * Immutable, stateless access to nested configuration arrays.
 *
 * @author  Mike van den Hoek
 * @license GPL-3.0-or-later
 *
 * @link    https://github.com/mvdhoek1/mvdh-config-resolver
 * @since   1.2.0
 */

declare(strict_types=1);

namespace MVDH\ConfigResolver;

/**
 * Read-only view on (a part of) a configuration array.
 *
 * Every method either returns a value or a new Config instance; an instance never changes after it
 * has been created. That makes it safe to share one instance, for example through a service container.
 *
 * Keys are resolved in two steps: an exact key at the current level wins, otherwise the key is
 * read as a dot-notated path. So both `['app.name' => 'x']` and `['app' => ['name' => 'x']]`
 * can be read with `get('app.name')`.
 *
 * @since 1.2.0
 */
final readonly class Config
{
    /**
     * @param  array<array-key, mixed>  $data
     */
    private function __construct(
        private array $data,
        private string $path,
    ) {}

    /**
     * Creates a Config for the given configuration array.
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function from(array $data): self
    {
        return new self($data, '');
    }

    /**
     * Returns a new Config scoped to the array at the given key or dot-notated path.
     *
     * @throws SettingNotFoundException If the path does not exist or does not point to an array.
     */
    public function in(string $path): self
    {
        [$found, $value] = $this->lookup($this->data, $path);

        if (! $found) {
            throw SettingNotFoundException::forSetting(
                $this->fullPath($path),
                array_map('strval', array_keys($this->data))
            );
        }

        if (! is_array($value)) {
            throw SettingNotFoundException::notAnArray($this->fullPath($path), get_debug_type($value));
        }

        return new self($value, $this->fullPath($path));
    }

    /**
     * Returns the value at the given key or dot-notated path, or $default when it does not exist.
     * A key that exists with the value null returns null, not $default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        [$found, $value] = $this->lookup($this->data, $key);

        return $found ? $value : $default;
    }

    /**
     * Same as get(), but throws when the key does not exist.
     *
     * @throws SettingNotFoundException If the key does not exist.
     */
    public function getOrFail(string $key): mixed
    {
        [$found, $value] = $this->lookup($this->data, $key);

        if (! $found) {
            throw SettingNotFoundException::forField($key, $this->path);
        }

        return $value;
    }

    /**
     * Checks whether the key or dot-notated path exists, also when its value is null.
     */
    public function has(string $key): bool
    {
        return $this->lookup($this->data, $key)[0];
    }

    /**
     * Returns the key at the current level whose value is exactly (===) $value, or null when there is none.
     * When several keys hold the value, the first one is returned.
     */
    public function keyOf(mixed $value): int|string|null
    {
        $key = array_search($value, $this->data, true);

        return $key === false ? null : $key;
    }

    /**
     * Returns the first row (an array at the current level) whose field is exactly (===) $value,
     * as a new Config, or null when no row matches. The field may be a dot-notated path inside the row.
     */
    public function find(string $field, mixed $value): ?self
    {
        foreach ($this->data as $key => $row) {
            if (! is_array($row)) {
                continue;
            }

            [$found, $candidate] = $this->lookup($row, $field);

            if ($found && $candidate === $value) {
                return new self($row, $this->fullPath((string) $key));
            }
        }

        return null;
    }

    /**
     * Same as find(), but throws when no row matches.
     *
     * @throws SettingNotFoundException If no row matches.
     */
    public function findOrFail(string $field, mixed $value): self
    {
        return $this->find($field, $value)
            ?? throw SettingNotFoundException::forMatch($field, $value, $this->path);
    }

    /**
     * Returns the array this Config is scoped to.
     *
     * @return array<array-key, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Returns the dot-notated path this Config is scoped to, or an empty string for the root.
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Resolves an exact key first, then a dot-notated path.
     *
     * @param  array<array-key, mixed>  $data
     * @return array{0: bool, 1: mixed} Whether the key exists, and its value.
     */
    private function lookup(array $data, string $key): array
    {
        if (array_key_exists($key, $data)) {
            return [true, $data[$key]];
        }

        if (! str_contains($key, '.')) {
            return [false, null];
        }

        $current = $data;

        foreach (explode('.', $key) as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return [false, null];
            }

            $current = $current[$segment];
        }

        return [true, $current];
    }

    private function fullPath(string $key): string
    {
        return $this->path === '' ? $key : $this->path.'.'.$key;
    }
}
