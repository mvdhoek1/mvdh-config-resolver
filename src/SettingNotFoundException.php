<?php

/**
 * @author  Mike van den Hoek
 * @license GPL-3.0-or-later
 *
 * @link    https://github.com/mvdhoek1/mvdh-config-resolver
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MVDH\ConfigResolver;

use InvalidArgumentException;

/**
 * Thrown when a setting path or a field cannot be resolved.
 * Extends InvalidArgumentException so existing catch blocks keep working.
 *
 * @since 1.0.0
 */
class SettingNotFoundException extends InvalidArgumentException
{
    /**
     * @param  list<string>  $availableKeys
     */
    public static function forSetting(string $setting, array $availableKeys): self
    {
        return new self(sprintf(
            'The requested setting "%s" is not configured. Available keys: %s',
            $setting,
            $availableKeys === [] ? '(none)' : implode(', ', $availableKeys)
        ));
    }

    public static function notAnArray(string $setting, string $type): self
    {
        return new self(sprintf(
            'The setting "%s" is not an array but %s.',
            $setting,
            $type
        ));
    }

    public static function forMatch(string $field, mixed $value, string $setting): self
    {
        $value = is_scalar($value) || $value === null ? var_export($value, true) : get_debug_type($value);

        return new self(sprintf(
            'No row found where "%s" is %s in %s.',
            $field,
            $value,
            $setting === '' ? 'the configuration' : sprintf('setting "%s"', $setting)
        ));
    }

    public static function forField(string $field, string $setting): self
    {
        if ($setting === '') {
            return new self(sprintf('Nothing found for "%s" in the configuration.', $field));
        }

        return new self(sprintf(
            'Nothing found for "%s" in setting "%s".',
            $field,
            $setting
        ));
    }
}
