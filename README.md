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

Use useSetting() to specify which part of the configuration to work with:

```php
$configResolver->useSetting('person');
```

This sets the configuration context to the person key.

### Simple Retrieval

Retrieve a value from the current context using get():

```php
$configResolver->get('name'); // Returns 'Dave'
```

If no field is specified, the entire context is returned:

```php
$configResolver->get(); // Returns the array for 'person'
```

### Returning Keys Instead of Values

Use returnKey() to retrieve the key corresponding to a value:

```php
$configResolver->returnKey(true)->get('Dave'); // Returns 'name'
$configResolver->returnKey(false)->get('name'); // Returns 'Dave'
```

### Searching by Field or Value

#### Search by Field

Use searchBy() to search for a value by its field:

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

#### Common Pitfall

When using returnKey(true) with searchBy(), ensure the value is passed as the second parameter:

```php
$configResolver->returnKey(true)->searchBy('name'); // Returns null because 'name' should have been 'Dave'.
```

### Working with Multi-Dimensional Arrays

Use searchBy() to retrieve values from arrays with multiple dimensions. First, set the context using useSetting():

```php
$configResolver->useSetting('person.options'); // Set to a multi-dimensional array
```

Then search within that context:

```php
$configResolver->returnKey(true)->searchBy('skill', 'smart'); // Returns the key 'skill'
$configResolver->returnKey(false)->searchBy('skill', 'smart'); // Returns the value 'smart'
```

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

// Use multi-dimensional arrays
$configResolver->useSetting('settings.database');
$dbUser = $configResolver->get('user'); // Returns 'root'
```

## Error Handling

The ConfigResolver class throws an InvalidArgumentException if a requested setting does not exist:

```php
try {
    $configResolver->useSetting('nonexistent.setting');
} catch (InvalidArgumentException $e) {
    echo $e->getMessage(); // Outputs: 'Wanted setting is not configured.'
}
```
