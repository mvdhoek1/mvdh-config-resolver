# ConfigResolver (deprecated)

> **Deprecated since 1.2.0, removed in 2.0.0.** Use the immutable `Config` class instead.
> See [Migrating from ConfigResolver](../README.md#migrating-from-configresolver).

`ConfigResolver` keeps its selected setting, search and `returnKey()` mode between calls. Don't share one instance between unrelated parts of your code.

## Usage

### Set-Up

To start using the `ConfigResolver`, initialize it with a configuration array:

```php
use MVDH\ConfigResolver\ConfigResolver;

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

### Setting the Configuration Context

Use `useSetting()` to specify which part of the configuration to work with. Nested keys are separated by dots:

```php
$configResolver->useSetting('person');
```

This sets the configuration context to the `person` key. Calling `useSetting()` also clears any search parameters set by a previous `searchBy()` call.

Until `useSetting()` has been called, lookups run against the top level of the configuration: `get('person')` returns the `person` array and `get()` returns the entire configuration.

Keys that contain a dot (e.g. `'app.name' => '...'`) cannot be selected with `useSetting()`, because the dot is always read as a separator. Select their parent and use `get('app.name')` instead.

### Simple Retrieval

Retrieve a value from the current context using `get()`. Unknown fields return `null`:

```php
$configResolver->get('name'); // Returns 'Dave'
```

If no field is specified, the entire context is returned:

```php
$configResolver->get(); // Returns the array for 'person'
```

### Returning Keys Instead of Values

Use `returnKey()` to retrieve the key corresponding to a value:

```php
$configResolver->returnKey(true)->get('Dave'); // Returns 'name'
$configResolver->returnKey(false)->get('name'); // Returns 'Dave'
```

> **Note:** `returnKey()` is a persistent mode. It stays active for every following call (also after `useSetting()`) until you call `returnKey(false)`.

> **Tip:** a resolver keeps its selected setting, search and `returnKey()` mode between calls. Avoid sharing one instance between unrelated parts of your code (for example as a singleton in a service container); create a new `ConfigResolver` where you need one, or use `clone`.

Keys are returned as-is, so a value at index `0` of a list returns `0` (not `null`). `null` is only returned when the value is not found.

### Searching by Field or Value

#### Search by Field

Use `searchBy()` to search for a value by its field:

```php
$configResolver->returnKey(false)->searchBy('name'); // Returns 'Dave'
$configResolver->searchBy('name'); // Same as above.
```

#### Search by Field and Value

Provide both the field and the expected value to perform a more specific search:

```php
$configResolver->returnKey(false)->searchBy('name', 'Dave'); // Returns 'Dave'
$configResolver->returnKey(true)->searchBy('name', 'Dave'); // Returns 'name'
```

When a value is passed, it is compared strictly (`===`), so `false`, `0` and `null` can be searched for as well:

```php
$configResolver->searchBy('active', false); // Matches only when 'active' is exactly false.
$configResolver->searchBy('parent', null);  // Matches only when 'parent' is exactly null.
```

Omitting the second argument (instead of passing `null`) searches by field only.

#### Common Pitfall

When using `returnKey(true)` with `searchBy()`, ensure the value is passed as the second parameter:

```php
$configResolver->returnKey(true)->searchBy('name'); // Returns null because 'name' should have been 'Dave'.
```

### Working with Multi-Dimensional Arrays

Use `searchBy()` to retrieve values from arrays with multiple dimensions (a list of rows, where every row is an array). First, set the context using `useSetting()`:

```php
$configResolver->useSetting('person.options'); // Set to a multi-dimensional array
```

Then search within that context:

```php
$configResolver->returnKey(true)->searchBy('skill', 'smart'); // Returns the key 'skill'
$configResolver->returnKey(false)->searchBy('skill', 'smart'); // Returns the value 'smart'
```

The search stays active after `searchBy()`, so you can use `get()` to read other fields from the first matching row:

```php
$configResolver->returnKey(false)->searchBy('skill', 'smart');
$configResolver->get('since'); // Returns 'teenager'
```

The search is cleared when you call `useSetting()` again or call `searchBy()` without a value.

## Example Use Case

Here’s an example of the complete workflow:

```php
$configArray = [
    'settings' => [
        'database' => [
            'host' => 'localhost',
            'user' => 'root',
            'password' => 'secret',
        ],
        'field_mapping' => [
            'userId' => 'UID',
            'email' => 'EmailAddress',
        ],
    ],
];

$configResolver = new ConfigResolver($configArray);

// Set context
$configResolver->useSetting('settings.field_mapping');

// Retrieve value
$field = $configResolver->get('userId'); // Returns 'UID'

// Search for a key by value
$key = $configResolver->returnKey(true)->get('EmailAddress'); // Returns 'email'

// Switch to another setting (returnKey is persistent, so turn it off first)
$configResolver->returnKey(false)->useSetting('settings.database');
$dbUser = $configResolver->get('user'); // Returns 'root'
```

## Checking for Missing Values

`get()` and `searchBy()` return `null` when nothing is found. Because a key can also hold `null` as its value, use `has()` to tell the two apart, or `getOrFail()` to throw when nothing is found:

```php
$configResolver = new ConfigResolver(['map' => ['empty' => null]]);
$configResolver->useSetting('map');

$configResolver->get('empty');       // null (the value is null)
$configResolver->get('unknown');     // null (the key is missing)

$configResolver->has('empty');       // true
$configResolver->has('unknown');     // false

$configResolver->getOrFail('empty');   // null
$configResolver->getOrFail('unknown'); // throws SettingNotFoundException
```

`has()` and `getOrFail()` follow the same rules as `get()`: they respect `returnKey()` and an active `searchBy()` search.

Use `get()` when a missing value is a normal case (e.g. an optional field mapping), and `getOrFail()` when a missing value means the configuration is wrong.

## Error Handling

`useSetting()` and `getOrFail()` throw a `MVDH\ConfigResolver\SettingNotFoundException` when something cannot be found. `useSetting()` also throws when a path tries to go deeper than a non-array value (e.g. `person.name.first`).

`SettingNotFoundException` extends `InvalidArgumentException`, so you can catch either:

```php
use MVDH\ConfigResolver\SettingNotFoundException;

try {
    $configResolver->useSetting('nonexistent.setting');
} catch (SettingNotFoundException $e) {
    echo $e->getMessage(); // Outputs: 'The requested setting "nonexistent.setting" is not configured. Available keys: settings'
}

try {
    $configResolver->useSetting('settings.database')->getOrFail('port');
} catch (SettingNotFoundException $e) {
    echo $e->getMessage(); // Outputs: 'Nothing found for "port" in setting "settings.database".'
}
```
