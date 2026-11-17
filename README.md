# Documentation

The `ConfigResolver` class is a utility designed to simplify the management and retrieval of configuration settings from a nested configuration array. It provides intuitive methods to set specific settings, search by fields or values, and retrieve values or keys.

---

## Features

- Retrieve values from nested configuration arrays.
- Dynamically set the configuration context.
- Search by field or value in one-dimensional and multi-dimensional arrays.
- Retrieve keys instead of values using the `returnKey` option.

---

## How to Use

### Set-Up

To start using the `ConfigResolver`, initialize it with a configuration array:

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

### Setting the Configuration Context

Use `useSetting()` to specify which part of the configuration to work with. Nested keys are separated by dots:

```php
$configResolver->useSetting('person');
```

This sets the configuration context to the `person` key. Calling `useSetting()` also clears any search parameters set by a previous `searchBy()` call.

Until `useSetting()` has been called, `get()` returns the entire configuration array.

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

## Error Handling

The `ConfigResolver` class throws an `InvalidArgumentException` if a requested setting does not exist, including when a path tries to go deeper than a non-array value (e.g. `person.name.first`):

```php
try {
    $configResolver->useSetting('nonexistent.setting');
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(); // Outputs: 'The requested setting "nonexistent.setting" is not configured. Available keys: settings'
}
```
