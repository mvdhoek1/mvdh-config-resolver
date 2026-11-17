<?php

declare(strict_types=1);

use MVDH\ConfigResolver\ConfigResolver;
use MVDH\ConfigResolver\SettingNotFoundException;

describe('useSetting', function () {
	it('returns the whole config when no setting is selected', function () {
		$resolver = resolver();

		expect($resolver->get())->toBe(personConfig());
		expect($resolver->get('person'))->toBe(personConfig());
	});

	it('selects a nested setting using dot notation', function () {
		$resolver = resolver();

		expect($resolver->useSetting('person.options')->get())
			->toBe(personConfig()['person']['options']);
	});

	it('is fluent', function () {
		$resolver = resolver();

		expect($resolver->useSetting('person'))->toBe($resolver);
	});

	it('throws when the setting does not exist', function () {
		$resolver = resolver();

		$resolver->useSetting('person.unknown');
	})->throws(SettingNotFoundException::class, 'The requested setting "person.unknown" is not configured. Available keys: name, surname, options');

	it('throws when the path goes through a non-array value', function () {
		$resolver = resolver();

		$resolver->useSetting('person.name.first');
	})->throws(SettingNotFoundException::class, 'Available keys: (none)');

	it('returns falsy settings instead of the whole config', function (mixed $value) {
		$resolver = new ConfigResolver(['other' => 'x', 'setting' => $value]);

		expect($resolver->useSetting('setting')->get())->toBe($value);
	})->with([
		'empty array' => [[]],
		'zero' => [0],
		'empty string' => [''],
		'string zero' => ['0'],
		'false' => [false],
		'null' => [null],
	]);

	it('clears search parameters from a previous searchBy call', function () {
		$resolver = resolver();

		$resolver->useSetting('person.options')->searchBy('skill', 'smart');

		expect($resolver->useSetting('person')->get('surname'))->toBe('Rossi');
	});

	it('keeps the returnKey mode', function () {
		$resolver = resolver();

		$resolver->returnKey()->useSetting('person');

		expect($resolver->get('Dave'))->toBe('name');
	});
});

describe('get', function () {
	it('returns the value of a field', function () {
		$resolver = resolver('person');

		expect($resolver->get('name'))->toBe('Dave');
	});

	it('returns the whole setting without a field', function () {
		$resolver = resolver('person');

		expect($resolver->get())->toBe(personConfig()['person']);
	});

	it('returns null for an unknown field', function () {
		$resolver = resolver('person');

		expect($resolver->get('unknown'))->toBeNull();
	});

	it('supports the field "0"', function () {
		$resolver = new ConfigResolver(['list' => ['a', 'b']]);

		expect($resolver->useSetting('list')->get('0'))->toBe('a');
	});

	it('returns null when the setting is not an array', function () {
		$resolver = resolver('person.name');

		expect($resolver->get('anything'))->toBeNull();
		expect($resolver->returnKey()->get('anything'))->toBeNull();
	});
});

describe('returnKey', function () {
	it('returns the key of a value', function () {
		$resolver = resolver('person');

		expect($resolver->returnKey(true)->get('Dave'))->toBe('name');
	});

	it('returns values again after returnKey(false)', function () {
		$resolver = resolver('person');

		$resolver->returnKey(true)->get('Dave');

		expect($resolver->returnKey(false)->get('name'))->toBe('Dave');
	});

	it('is a persistent mode', function () {
		$resolver = resolver('person');

		$resolver->returnKey();

		expect($resolver->get('Dave'))->toBe('name');
		expect($resolver->get('Rossi'))->toBe('surname');
	});

	it('returns null when the value is not found', function () {
		$resolver = resolver('person');

		expect($resolver->returnKey()->get('Unknown'))->toBeNull();
	});

	it('returns key 0 instead of null', function () {
		$resolver = new ConfigResolver(['list' => ['a', 'b']]);

		expect($resolver->useSetting('list')->returnKey()->get('a'))->toBe(0);
	});

	it('compares values strictly', function () {
		$resolver = new ConfigResolver(['map' => ['one' => 1, 'string' => '1']]);

		expect($resolver->useSetting('map')->returnKey()->get('1'))->toBe('string');
	});
});

describe('searchBy on a one-dimensional setting', function () {
	it('searches by field only', function () {
		$resolver = resolver('person');

		expect($resolver->searchBy('name'))->toBe('Dave');
	});

	it('returns the value when field and value match', function () {
		$resolver = resolver('person');

		expect($resolver->searchBy('name', 'Dave'))->toBe('Dave');
	});

	it('returns null when the value does not match', function () {
		$resolver = resolver('person');

		expect($resolver->searchBy('name', 'John'))->toBeNull();
	});

	it('returns the key with returnKey', function () {
		$resolver = resolver('person');

		expect($resolver->returnKey()->searchBy('name', 'Dave'))->toBe('name');
	});

	it('does not return a different field holding the same value', function () {
		$resolver = new ConfigResolver(['map' => ['a' => 'x', 'b' => 'y']]);

		expect($resolver->useSetting('map')->returnKey()->searchBy('b', 'x'))->toBeNull();
	});

	it('returns null with returnKey when no value is given', function () {
		$resolver = resolver('person');

		expect($resolver->returnKey()->searchBy('name'))->toBeNull();
	});

	it('keeps the search active for following get calls', function () {
		$resolver = resolver('person');

		$resolver->searchBy('name', 'Dave');

		expect($resolver->get('surname'))->toBe('Rossi');
	});

	it('clears the search when called without a value', function () {
		$resolver = resolver('person');

		$resolver->searchBy('name', 'John');
		$resolver->searchBy('name');

		expect($resolver->get('surname'))->toBe('Rossi');
	});

	it('can search for falsy values', function (mixed $value) {
		$resolver = new ConfigResolver(['flags' => ['on' => $value]]);

		expect($resolver->useSetting('flags')->returnKey()->searchBy('on', $value))->toBe('on');
	})->with([
		'false' => [false],
		'null' => [null],
		'zero' => [0],
		'empty string' => [''],
	]);

	it('compares search values strictly', function () {
		$resolver = new ConfigResolver(['flags' => ['on' => 0]]);

		expect($resolver->useSetting('flags')->returnKey()->searchBy('on', false))->toBeNull();
	});
});

describe('searchBy on a multi-dimensional setting', function () {
	it('returns the matched value', function () {
		$resolver = resolver('person.options');

		expect($resolver->searchBy('skill', 'smart'))->toBe('smart');
	});

	it('returns the field with returnKey', function () {
		$resolver = resolver('person.options');

		expect($resolver->returnKey()->searchBy('skill', 'smart'))->toBe('skill');
	});

	it('returns null when nothing matches', function () {
		$resolver = resolver('person.options');

		expect($resolver->searchBy('skill', 'unknown'))->toBeNull();
		expect($resolver->returnKey()->searchBy('skill', 'unknown'))->toBeNull();
	});

	it('reads other fields from the first matching row', function () {
		$resolver = resolver('person.options');

		$resolver->searchBy('skill', 'smart');

		expect($resolver->get('since'))->toBe('teenager');
	});

	it('returns null for an unknown field in the matching row', function () {
		$resolver = resolver('person.options');

		$resolver->searchBy('skill', 'smart');

		expect($resolver->get('unknown'))->toBeNull();
	});

	it('can search for null', function () {
		$resolver = new ConfigResolver(['rows' => [
			['id' => 1, 'parent' => 'x'],
			['id' => 2, 'parent' => null],
		]]);

		$resolver->useSetting('rows')->searchBy('parent', null);

		expect($resolver->get('id'))->toBe(2);
	});

	it('does not treat a missing field as null', function () {
		$resolver = new ConfigResolver(['rows' => [['id' => 1], ['id' => 2, 'parent' => null]]]);

		$resolver->useSetting('rows')->searchBy('parent', null);

		expect($resolver->get('id'))->toBe(2);
	});

	it('supports lists that do not start at index 0', function () {
		$resolver = new ConfigResolver(['rows' => [5 => ['a' => 'x', 'b' => 'y']]]);

		$resolver->useSetting('rows')->searchBy('a', 'x');

		expect($resolver->get('b'))->toBe('y');
	});

	it('treats an associative array with key 0 and scalar values as one-dimensional', function () {
		$resolver = new ConfigResolver(['map' => [0 => 'zero', 'name' => 'Dave']]);

		expect($resolver->useSetting('map')->searchBy('name', 'Dave'))->toBe('Dave');
	});
});

describe('has', function () {
	it('returns true for an existing field', function () {
		expect(resolver('person')->has('name'))->toBeTrue();
	});

	it('returns false for a missing field', function () {
		expect(resolver('person')->has('unknown'))->toBeFalse();
	});

	it('distinguishes a missing key from a null value', function () {
		$resolver = (new ConfigResolver(['map' => ['empty' => null]]))->useSetting('map');

		expect($resolver->get('empty'))->toBeNull();
		expect($resolver->get('unknown'))->toBeNull();
		expect($resolver->has('empty'))->toBeTrue();
		expect($resolver->has('unknown'))->toBeFalse();
	});

	it('checks values in returnKey mode', function () {
		$resolver = resolver('person')->returnKey();

		expect($resolver->has('Dave'))->toBeTrue();
		expect($resolver->has('John'))->toBeFalse();
	});

	it('respects an active search', function () {
		$resolver = resolver('person.options');

		$resolver->searchBy('skill', 'smart');
		expect($resolver->has('since'))->toBeTrue();
		expect($resolver->has('unknown'))->toBeFalse();

		$resolver->searchBy('skill', 'unknown');
		expect($resolver->has('since'))->toBeFalse();
	});

	it('returns false when the setting is not an array', function () {
		expect(resolver('person.name')->has('anything'))->toBeFalse();
	});
});

describe('getOrFail', function () {
	it('returns the value of an existing field', function () {
		expect(resolver('person')->getOrFail('name'))->toBe('Dave');
	});

	it('returns null values without throwing', function () {
		$resolver = (new ConfigResolver(['map' => ['empty' => null]]))->useSetting('map');

		expect($resolver->getOrFail('empty'))->toBeNull();
	});

	it('throws for a missing field', function () {
		resolver('person')->getOrFail('unknown');
	})->throws(SettingNotFoundException::class, 'Nothing found for "unknown" in setting "person".');

	it('throws when a search does not match', function () {
		$resolver = resolver('person.options');
		$resolver->searchBy('skill', 'unknown');

		$resolver->getOrFail('since');
	})->throws(SettingNotFoundException::class);

	it('returns keys in returnKey mode', function () {
		expect(resolver('person')->returnKey()->getOrFail('Dave'))->toBe('name');
	});

	it('throws an exception that existing InvalidArgumentException handlers catch', function () {
		expect(new SettingNotFoundException())->toBeInstanceOf(InvalidArgumentException::class);
	});
});

describe('README example', function () {
	it('resolves the documented workflow', function () {
		$resolver = new ConfigResolver([
			'settings' => [
				'database' => ['host' => 'localhost', 'user' => 'root', 'password' => 'secret'],
				'field_mapping' => ['userId' => 'UID', 'email' => 'EmailAddress'],
			],
		]);

		$resolver->useSetting('settings.field_mapping');
		expect($resolver->get('userId'))->toBe('UID');
		expect($resolver->returnKey(true)->get('EmailAddress'))->toBe('email');

		$resolver->returnKey(false)->useSetting('settings.database');
		expect($resolver->get('user'))->toBe('root');
	});
});
