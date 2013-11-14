<?php
class DataTableHiddenBuilder {
	/** @var string */
	protected $value;
	/** @var  string Name of form element */
	protected $name;

	/**
	 * @return DataTableHiddenBuilder
	 */
	public static function create() {
		return new DataTableHiddenBuilder();
	}

	/**
	 * @param $value string
	 * @return DataTableHiddenBuilder
	 */
	public function value($value) {
		$this->value = $value;
		return $this;
	}

	/**
	 * @param $name string
	 * @return DataTableHiddenBuilder
	 */
	public function name($name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * @return DataTableHidden
	 * @throws Exception
	 */
	public function build() {
		if (is_null($this->name)) {
			throw new Exception("name is missing");
		}
		if (!is_string($this->name)) {
			throw new Exception("name must be a string");
		}

		if (is_null($this->value)) {
			$this->value = "";
		}
		if (!is_string($this->value)) {
			throw new Exception("value must be a string");
		}

		return new DataTableHidden($this);
	}
}