<?php

declare(strict_types=1);

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
function resolver(?string $setting = null): MVDH\ConfigResolver\ConfigResolver
{
    $resolver = new MVDH\ConfigResolver\ConfigResolver(personConfig());

    return null === $setting ? $resolver : $resolver->useSetting($setting);
}
