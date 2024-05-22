# mvdh-config-resolver
The ConfigResolver class is designed to manage and retrieve configuration settings from a nested configuration array. It provides methods to set specific settings, search by fields, and retrieve values or keys based on the configuration.

## How to use

### Set-up
```php
$configArray = [
	'person' => [
		'name' => 'Dave',
		'surname' => 'Rossi',
		'options' => [
			[
				'skill' => 'cute',
				'since' => 'baby',
			],
			[
				'skill' => 'smart',
				'since' => 'teenager',
			],
		],
	],
];

$configResolver = new ConfigResolver($configArray);
```

### Set the specified setting to be used
```php
$configResolver->useSetting('person');
```

### Simple retrieval
```php
$configResolver->get('name'); // returns the value 'Dave'
```

### With the return key option
```php
$configResolver->returnKey(true)->get('Dave'); // returns the key 'name'
$configResolver->returnKey(false)->get('name'); // returns the value 'Dave'
```

### With the return key option and the search by value
```php
// When we don't want to return the key we must use the key as the first parameter.
$configResolver->returnKey(false)->searchBy('name'); // Returns the value 'Dave'
$configResolver->searchBy('name'); // Does the same.
$configResolver->returnKey(false)->searchBy('name', 'Dave'); // Does the same but a bit more clear.

// When we do want to return the key we must use the wanted value as the first parameter.
$configResolver->returnKey(true)->searchBy('Dave'); // Returns the key 'name'.
$configResolver->returnKey(true)->searchBy('name', 'Dave'); // Does the same but a bit more clear.

// And for demonstrating purposes an example of how to do this wrong.
$configResolver->returnKey(true)->searchBy('name'); // Returns null because 'name' should have been 'Dave'.
```

### Search in multi-dimensional arrays
```php
$configResolver->useSetting('person.options'); // Make sure this is a multi-dimensional array
$configResolver->returnKey(true)->searchBy('skill', 'smart'); // returns the key 'skill'
$configResolver->returnKey(false)->searchBy('skill', 'smart'); // returns the value 'smart'
```
