<?php

declare(strict_types=1);

namespace MVDH\ConfigResolver;

class ConfigResolver
{
	private array $config; // Array which holds all the data from the config file.
	private mixed $setting = null; // Setting from the config file to use.
	private string $settingPath = ''; // Dot-notated path of the selected setting.
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
	 * @throws SettingNotFoundException If the wanted setting is not configured.
	 */
	public function useSetting(string $setting): self
	{
		$settings = explode('.', $setting);
		$currentConfig = $this->config;

		foreach ($settings as $key) {
			if (! is_array($currentConfig) || ! array_key_exists($key, $currentConfig)) {
				throw SettingNotFoundException::forSetting(
					$setting,
					is_array($currentConfig) ? array_map('strval', array_keys($currentConfig)) : []
				);
			}

			$currentConfig = $currentConfig[$key];
		}

		$this->setting = $currentConfig;
		$this->settingPath = $setting;
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
	 * Returns null when nothing is found; use has() or getOrFail() to tell a missing key from a null value.
	 */
	public function get(string $field = ''): mixed
	{
		return $this->resolve($field)[1];
	}

	/**
	 * Checks whether get() with the same arguments would find something.
	 * Unlike get(), this distinguishes a missing key from a key whose value is null.
	 */
	public function has(string $field): bool
	{
		return $this->resolve($field)[0];
	}

	/**
	 * Same as get(), but throws when nothing is found.
	 *
	 * @throws SettingNotFoundException If nothing is found.
	 */
	public function getOrFail(string $field): mixed
	{
		[$found, $value] = $this->resolve($field);

		if (! $found) {
			throw SettingNotFoundException::forField($field, $this->settingPath);
		}

		return $value;
	}

	/**
	 * @return array{0: bool, 1: mixed} Whether something was found, and the found value.
	 */
	private function resolve(string $field): array
	{
		if (! $this->hasSetting) {
			return [true, $this->config];
		}

		if ('' === $field) {
			return [true, $this->setting];
		}

		if (! is_array($this->setting)) {
			return [false, null];
		}

		if ('' === $this->fieldToMatch || ! $this->hasValueToMatch) {
			return $this->handleWithoutSearchParams($field);
		}

		if ($this->isMultiDimensional($this->setting)) {
			return $this->handleMultiDimensional($field);
		}

		return $this->handleOneDimensional($field);
	}

	/**
	 * @return array{0: bool, 1: mixed}
	 */
	protected function handleWithoutSearchParams(string $field): array
	{
		if ($this->returnKey) {
			$key = array_search($field, $this->setting, true);

			return false === $key ? [false, null] : [true, $key];
		}

		return $this->lookup($field, $this->setting);
	}

	/**
	 * @return array{0: bool, 1: mixed}
	 */
	protected function handleOneDimensional(string $field): array
	{
		if (! $this->matches($this->setting)) {
			return [false, null];
		}

		if ($this->returnKey) {
			return [true, $this->fieldToMatch];
		}

		return $this->lookup($field, $this->setting);
	}

	/**
	 * Handles retrieving values for multi-dimensional arrays during search.
	 *
	 * @return array{0: bool, 1: mixed}
	 */
	protected function handleMultiDimensional(string $field): array
	{
		foreach ($this->setting as $row) {
			if (! $this->matches($row)) {
				continue;
			}

			if ($this->returnKey) {
				return [true, $this->fieldToMatch];
			}

			return $this->lookup($field, $row);
		}

		return [false, null];
	}

	private function matches(mixed $row): bool
	{
		return is_array($row)
			&& array_key_exists($this->fieldToMatch, $row)
			&& $row[$this->fieldToMatch] === $this->valueToMatch;
	}

	/**
	 * @return array{0: bool, 1: mixed}
	 */
	private function lookup(string $field, array $haystack): array
	{
		return array_key_exists($field, $haystack) ? [true, $haystack[$field]] : [false, null];
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
}
