<?php

declare(strict_types=1);

use InvalidArgumentException;

class ConfigResolver
{
	private array $config; // Array which holds all the data from the config file.
	private mixed $setting = null; // Setting from the config file to use.
	private string $fieldToMatch = '';
	private mixed $valueToMatch = null;
	private bool $returnKey = false;

	public function __construct(array $config)
	{
		$this->config = $config;
	}

	/**
	 * Sets the specified setting to be used.
	 *
	 * @throws InvalidArgumentException If the wanted setting is not configured.
	 */
	public function useSetting(string $setting): self
	{
		$settings = explode('.', $setting);
		$currentConfig = $this->config;

		foreach ($settings as $key) {
			if (! array_key_exists($key, $currentConfig)) {
				throw new InvalidArgumentException(sprintf(
					'The requested setting "%s" is not configured. Available keys: %s',
					$setting,
					implode(', ', array_keys($currentConfig))
				));
			}

			$currentConfig = $currentConfig[$key];
		}

		$this->setting = $currentConfig;

		return $this;
	}

	/**
	 * Sets the field and value to be used for searching.
	 */
	public function searchBy(string $field, mixed $value = null): mixed
	{
		$this->fieldToMatch = $field;
		$this->valueToMatch = $value;

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
		if (empty($this->setting)) {
			return $this->config;
		}

		if (empty($field)) {
			return $this->setting;
		}

		if (empty($this->fieldToMatch) || (null === $this->valueToMatch && false !== $this->valueToMatch)) {
			return $this->handleWithoutSearchParams($field);
		}

		if (is_array($this->setting) && array_key_exists(0, $this->setting)) {
			return $this->handleMultiDimensional($field);
		}

		return $this->handleOneDimensional($field);
	}

	protected function handleWithoutSearchParams(string $field): mixed
	{
		if ($this->returnKey) {
			return array_search($field, $this->setting, true) ?: null;
		}

		return $this->setting[$field] ?? null;
	}

	protected function handleOneDimensional(string $field): mixed
	{
		if ($this->returnKey) {
			if (null !== $this->valueToMatch) {
				return array_search($this->valueToMatch, $this->setting, true) ?: null;
			}

			return array_search($field, $this->setting, true) ?: null;
		}

		return ($this->setting[$field] ?? null) === $this->valueToMatch ? $this->setting[$field] : null;
	}

	/**
	 * Handles retrieving values for multi-dimensional arrays during search.
	 */
	protected function handleMultiDimensional(string $field): mixed
	{
		foreach ($this->setting as $setting) {
			if (($setting[$this->fieldToMatch] ?? null) !== $this->valueToMatch) {
				continue;
			}

			if ($this->returnKey) {
				return array_search($this->valueToMatch, $setting, true) ?: null;
			}

			return $setting[$field] ?? null;
		}

		return null;
	}
}
