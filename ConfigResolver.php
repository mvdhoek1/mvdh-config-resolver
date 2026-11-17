<?php

declare(strict_types=1);

class ConfigResolver
{
	private array $config; // Array which holds all the data from the config file.
	private mixed $setting = null; // Setting from the config file to use.
	private bool $hasSetting = false; // Whether a setting has been selected with useSetting().
	private string $fieldToMatch = '';
	private mixed $valueToMatch = null;
	private bool $hasValueToMatch = false; // Distinguishes searching for null from not searching by value.
	private bool $returnKey = false;

	public function __construct(array $config)
	{
		$this->config = $config;
	}

	/**
	 * Sets the specified setting to be used.
	 * Resets any search parameters set by a previous searchBy() call.
	 *
	 * @throws InvalidArgumentException If the wanted setting is not configured.
	 */
	public function useSetting(string $setting): self
	{
		$settings = explode('.', $setting);
		$currentConfig = $this->config;

		foreach ($settings as $key) {
			if (! is_array($currentConfig) || ! array_key_exists($key, $currentConfig)) {
				throw new InvalidArgumentException(sprintf(
					'The requested setting "%s" is not configured. Available keys: %s',
					$setting,
					is_array($currentConfig) ? implode(', ', array_keys($currentConfig)) : '(none)'
				));
			}

			$currentConfig = $currentConfig[$key];
		}

		$this->setting = $currentConfig;
		$this->hasSetting = true;
		$this->fieldToMatch = '';
		$this->valueToMatch = null;
		$this->hasValueToMatch = false;

		return $this;
	}

	/**
	 * Sets the field and value to be used for searching.
	 * Omit the value to search by field only; pass null explicitly to search for null values.
	 */
	public function searchBy(string $field, mixed $value = null): mixed
	{
		$this->fieldToMatch = $field;
		$this->valueToMatch = $value;
		$this->hasValueToMatch = func_num_args() > 1;

		return $this->get($field);
	}

	/**
	 * Returns the key of the searched value.
	 */
	public function returnKey(bool $value = true): self
	{
		$this->returnKey = $value;

		return $this;
	}

	/**
	 * Retrieves the value of the specified key inside an array.
	 * If no setting or key is specified the entire configuration will be returned.
	 */
	public function get(string $field = ''): mixed
	{
		if (! $this->hasSetting) {
			return $this->config;
		}

		if ('' === $field) {
			return $this->setting;
		}

		if (! is_array($this->setting)) {
			return null;
		}

		if ('' === $this->fieldToMatch || ! $this->hasValueToMatch) {
			return $this->handleWithoutSearchParams($field);
		}

		if ($this->isMultiDimensional($this->setting)) {
			return $this->handleMultiDimensional($field);
		}

		return $this->handleOneDimensional($field);
	}

	protected function handleWithoutSearchParams(string $field): mixed
	{
		if ($this->returnKey) {
			return $this->searchKey($field, $this->setting);
		}

		return $this->setting[$field] ?? null;
	}

	protected function handleOneDimensional(string $field): mixed
	{
		if (! array_key_exists($this->fieldToMatch, $this->setting) || $this->setting[$this->fieldToMatch] !== $this->valueToMatch) {
			return null;
		}

		if ($this->returnKey) {
			return $this->fieldToMatch;
		}

		return $this->setting[$field] ?? null;
	}

	/**
	 * Handles retrieving values for multi-dimensional arrays during search.
	 */
	protected function handleMultiDimensional(string $field): mixed
	{
		foreach ($this->setting as $setting) {
			if (! is_array($setting) || ! array_key_exists($this->fieldToMatch, $setting) || $setting[$this->fieldToMatch] !== $this->valueToMatch) {
				continue;
			}

			if ($this->returnKey) {
				return $this->fieldToMatch;
			}

			return $setting[$field] ?? null;
		}

		return null;
	}

	/**
	 * A setting is multi-dimensional when it is a non-empty list of arrays (rows).
	 */
	private function isMultiDimensional(array $setting): bool
	{
		if ([] === $setting) {
			return false;
		}

		foreach ($setting as $row) {
			if (! is_array($row)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns the key of the given value, or null when not found. Preserves falsy keys such as 0.
	 */
	private function searchKey(mixed $value, array $haystack): int|string|null
	{
		$key = array_search($value, $haystack, true);

		return false === $key ? null : $key;
	}
}
