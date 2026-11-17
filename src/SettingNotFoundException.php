<?php

declare(strict_types=1);

namespace MVDH\ConfigResolver;

use InvalidArgumentException;

/**
 * Thrown when a setting path or a field cannot be resolved.
 * Extends InvalidArgumentException so existing catch blocks keep working.
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
