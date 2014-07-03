<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

/**
 * Builder for DataTableHidden
 */
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
	 * Validate input and create DataTableHidden
	 *
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
			$this->value = (string)$this->value;
		}

		return new DataTableHidden($this);
	}
}