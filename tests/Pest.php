<?php

declare(strict_types=1);
use MVDH\ConfigResolver\ConfigResolver;

/*
|--------------------------------------------------------------------------
| Shared fixtures
|--------------------------------------------------------------------------
*/

function personConfig(): array
{
    return [
        'person' => [
            'name' => 'Dave',
            'surname' => 'Rossi',
            'options' => [
                ['skill' => 'cute', 'since' => 'baby'],
                ['skill' => 'smart', 'since' => 'teenager'],
            ],
        ],
    ];
}

/**
 * Creates a resolver for the person fixture, optionally with a setting selected.
 */
function resolver(?string $setting = null): ConfigResolver
{
    $resolver = new ConfigResolver(personConfig());

    return $setting === null ? $resolver : $resolver->useSetting($setting);
}
