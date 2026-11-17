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
    public static function forSetting(string $setting, array $availableKeys): self
    {
        return new self(sprintf(
            'The requested setting "%s" is not configured. Available keys: %s',
            $setting,
            $availableKeys === [] ? '(none)' : implode(', ', $availableKeys)
        ));
    }

    public static function forField(string $field, string $setting): self
    {
        return new self(sprintf(
            'Nothing found for "%s" in setting "%s".',
            $field,
            $setting
        ));
    }
}
