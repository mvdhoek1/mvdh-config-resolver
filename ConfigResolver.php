<?php

declare(strict_types=1);

use InvalidArgumentException;

class ConfigResolver
{
	private array $config; // Array which holds all the data from the config file.
	private $setting; // Setting from the config file to use.
	private string $fieldToMatch = '';
	private $valueToMatch = null;
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
		$currentConfig = $this->config;  // Acts as a holder.

		foreach ($settings as $key) {
			if (empty($currentConfig[$key])) {
				throw new InvalidArgumentException('Wanted setting is not configured: ' . $setting);
			}

			$currentConfig = $currentConfig[$key];  // Overwrite with the new setting.
		}

		$this->setting = $currentConfig;

		return $this;
	}

	/**
	 * Sets the field and value to be used for searching.
	 */
	public function searchBy(string $field, $value = null)
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
	public function get(string $field = '')
	{
		if (empty($this->setting)) {
			return $this->config;
		}

		if (empty($field)) {
			return $this->setting;
		}

		if (empty($this->fieldToMatch) || (empty($this->valueToMatch) && false !== $this->valueToMatch)) {
			return $this->handleWithoutSearchParams($field);
		}

		if (is_array($this->setting) && array_key_exists(0, $this->setting)) {
			return $this->handleMultiDimensional($field);
		}

		return $this->handleOneDimensional($field);
	}

	protected function handleWithoutSearchParams(string $field)
	{
		if ($this->returnKey) {
			return array_search($field, $this->setting) ?: null;
		}

		$value = $this->setting[$field] ?? null;

		return $value ? $value : null;
	}

	protected function handleOneDimensional(string $field)
	{
		if ($this->returnKey) {
			if (! empty($this->valueToMatch)) {
				return array_search($this->valueToMatch, $this->setting) ?: null;
			}

			return array_search($field, $this->setting) ?: null;
		}

		$value = $this->setting[$field] ?? null;

		return $value === $this->valueToMatch ? $value : null;
	}

	/**
	 * Handles retrieving values for multi-dimensional arrays during search.
	 */
	protected function handleMultiDimensional(string $field)
	{
		foreach ($this->setting as $setting) {
			if ((empty($setting[$this->fieldToMatch]) && false !== $setting[$this->fieldToMatch]) || $setting[$this->fieldToMatch] !== $this->valueToMatch) {
				continue;
			}

			if ($this->returnKey) {
				return array_search($this->valueToMatch, $setting) ?: null;
			}

			return $setting[$field] ?? null;
		}

		return null;
	}
}
