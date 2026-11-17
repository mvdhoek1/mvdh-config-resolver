<?php

declare(strict_types=1);

use MVDH\ConfigResolver\Config;
use MVDH\ConfigResolver\SettingNotFoundException;

function config(): Config
{
    return Config::from(personConfig());
}

describe('from', function () {
    it('wraps the whole configuration', function () {
        expect(config()->all())->toBe(personConfig());
        expect(config()->path())->toBe('');
    });
});

describe('in', function () {
    it('scopes to a nested array using dot notation', function () {
        $options = config()->in('person.options');

        expect($options->all())->toBe(personConfig()['person']['options']);
        expect($options->path())->toBe('person.options');
    });

    it('can be chained and keeps track of the full path', function () {
        expect(config()->in('person')->in('options')->path())->toBe('person.options');
    });

    it('throws for a missing path and lists the available keys', function () {
        config()->in('person.unknown');
    })->throws(SettingNotFoundException::class, 'The requested setting "person.unknown" is not configured. Available keys: person');

    it('includes the parent path in the message when chained', function () {
        config()->in('person')->in('unknown');
    })->throws(SettingNotFoundException::class, 'The requested setting "person.unknown" is not configured. Available keys: name, surname, options');

    it('throws when the path points to a value that is not an array', function () {
        config()->in('person.name');
    })->throws(SettingNotFoundException::class, 'The setting "person.name" is not an array but string.');

    it('throws when the path goes through a value that is not an array', function () {
        config()->in('person.name.first');
    })->throws(SettingNotFoundException::class);

    it('scopes to an empty array', function () {
        expect(Config::from(['empty' => []])->in('empty')->all())->toBe([]);
    });
});

describe('get', function () {
    it('returns a value at the current level', function () {
        expect(config()->in('person')->get('name'))->toBe('Dave');
    });

    it('returns a value using dot notation', function () {
        expect(config()->get('person.name'))->toBe('Dave');
        expect(config()->get('person.options.1.skill'))->toBe('smart');
    });

    it('returns null or the default for missing keys', function () {
        expect(config()->get('unknown'))->toBeNull();
        expect(config()->get('person.unknown', 'fallback'))->toBe('fallback');
        expect(config()->get('person.name.first', 'fallback'))->toBe('fallback');
    });

    it('returns null instead of the default when the value is null', function () {
        expect(Config::from(['empty' => null])->get('empty', 'fallback'))->toBeNull();
    });

    it('returns falsy values as they are', function (mixed $value) {
        expect(Config::from(['value' => $value])->get('value', 'fallback'))->toBe($value);
    })->with([
        'empty array' => [[]],
        'zero' => [0],
        'empty string' => [''],
        'string zero' => ['0'],
        'false' => [false],
    ]);

    it('prefers an exact key that contains a dot over a nested path', function () {
        $config = Config::from([
            'app.name' => 'flat',
            'app' => ['name' => 'nested', 'version' => '1.0'],
        ]);

        expect($config->get('app.name'))->toBe('flat');
        expect($config->get('app.version'))->toBe('1.0');
    });

    it('reads numeric keys', function () {
        expect(Config::from(['list' => ['a', 'b']])->get('list.1'))->toBe('b');
        expect(Config::from(['a', 'b'])->get('0'))->toBe('a');
    });
});

describe('has', function () {
    it('tells a missing key apart from a null value', function () {
        $config = Config::from(['map' => ['empty' => null]]);

        expect($config->has('map.empty'))->toBeTrue();
        expect($config->has('map.unknown'))->toBeFalse();
        expect($config->in('map')->has('empty'))->toBeTrue();
    });
});

describe('getOrFail', function () {
    it('returns existing values, including null', function () {
        expect(config()->getOrFail('person.surname'))->toBe('Rossi');
        expect(Config::from(['empty' => null])->getOrFail('empty'))->toBeNull();
    });

    it('throws for a missing key in the root', function () {
        config()->getOrFail('unknown');
    })->throws(SettingNotFoundException::class, 'Nothing found for "unknown" in the configuration.');

    it('throws for a missing key in a scoped config', function () {
        config()->in('person')->getOrFail('age');
    })->throws(SettingNotFoundException::class, 'Nothing found for "age" in setting "person".');
});

describe('keyOf', function () {
    it('returns the key of a value', function () {
        $mapping = Config::from(['userId' => 'UID', 'email' => 'EmailAddress']);

        expect($mapping->keyOf('EmailAddress'))->toBe('email');
    });

    it('returns integer keys as they are', function () {
        expect(Config::from(['a', 'b'])->keyOf('a'))->toBe(0);
    });

    it('compares strictly', function () {
        $config = Config::from(['zero' => 0, 'off' => false, 'none' => null]);

        expect($config->keyOf(false))->toBe('off');
        expect($config->keyOf(null))->toBe('none');
        expect($config->keyOf('0'))->toBeNull();
    });

    it('returns null when the value is not found', function () {
        expect(config()->in('person')->keyOf('Unknown'))->toBeNull();
    });
});

describe('find', function () {
    it('returns the first matching row as a scoped config', function () {
        $row = config()->in('person.options')->find('skill', 'smart');

        expect($row)->toBeInstanceOf(Config::class);
        expect($row?->get('since'))->toBe('teenager');
        expect($row?->path())->toBe('person.options.1');
    });

    it('returns null when no row matches', function () {
        expect(config()->in('person.options')->find('skill', 'unknown'))->toBeNull();
    });

    it('compares strictly', function () {
        $rows = Config::from([['id' => 1], ['id' => '1']]);

        expect($rows->find('id', '1')?->path())->toBe('1');
        expect($rows->find('id', true))->toBeNull();
    });

    it('can search for null', function () {
        $rows = Config::from([['parent' => 'a'], ['parent' => null], ['other' => 'x']]);

        expect($rows->find('parent', null)?->path())->toBe('1');
    });

    it('skips rows that are not arrays', function () {
        $rows = Config::from(['skip' => 'me', 'row' => ['id' => 1]]);

        expect($rows->find('id', 1)?->path())->toBe('row');
    });

    it('supports dot notation inside a row', function () {
        $rows = Config::from([
            'users' => [
                ['name' => 'Ann', 'address' => ['city' => 'Utrecht']],
                ['name' => 'Bob', 'address' => ['city' => 'Breda']],
            ],
        ]);

        expect($rows->in('users')->find('address.city', 'Breda')?->get('name'))->toBe('Bob');
    });
});

describe('findOrFail', function () {
    it('returns the matching row', function () {
        expect(config()->in('person.options')->findOrFail('skill', 'cute')->get('since'))->toBe('baby');
    });

    it('throws when no row matches', function () {
        config()->in('person.options')->findOrFail('skill', 'lazy');
    })->throws(SettingNotFoundException::class, 'No row found where "skill" is \'lazy\' in setting "person.options".');

    it('describes non-scalar values by type', function () {
        Config::from([])->findOrFail('id', [1]);
    })->throws(SettingNotFoundException::class, 'No row found where "id" is array in the configuration.');
});

describe('immutability', function () {
    it('never changes an existing instance', function () {
        $config = config();
        $before = $config->all();

        $config->in('person');
        $config->in('person.options')->find('skill', 'smart');
        $config->keyOf('Dave');

        expect($config->all())->toBe($before);
        expect($config->path())->toBe('');
    });

    it('gives the same result regardless of earlier calls on a shared instance', function () {
        $config = Config::from([
            'settings' => [
                'database' => ['user' => 'root'],
                'field_mapping' => ['userId' => 'UID', 'email' => 'EmailAddress'],
            ],
        ]);

        expect($config->in('settings.field_mapping')->keyOf('EmailAddress'))->toBe('email');
        expect($config->in('settings.database')->get('user'))->toBe('root');
    });

    it('cannot be modified from outside', function () {
        expect((new ReflectionClass(Config::class))->isReadOnly())->toBeTrue();
        expect((new ReflectionClass(Config::class))->isFinal())->toBeTrue();
    });
});
