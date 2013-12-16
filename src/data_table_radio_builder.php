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
 * Radio button builder.
 *
 * TODO: behavior implementation. This is a bit tricker than other elements since we need to iterate through
 * all radio buttons for the given field
 */
class DataTableRadioBuilder {
	/**
	 * @var string Field name
	 */
	protected $name;
	/**
	 * @var string Checkbox value
	 */
	protected $value;
	/**
	 * @var bool Checked by default?
	 */
	protected $checked_by_default;
	/**
	 * @var string Either 'top' or 'bottom'
	 */
	protected $placement;
	/**
	 * @var string HTML label
	 */
	protected $label;

	public static function create() {
		return new DataTableCheckboxBuilder();
	}

	/**
	 * @param $name string Field name
	 * @return DataTableCheckboxBuilder
	 */
	public function name($name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * @param $value string Checkbox value
	 * @return DataTableCheckboxBuilder
	 */
	public function value($value) {
		$this->value = $value;
		return $this;
	}

	/**
	 * @param $checked_by_default bool Checked by default?
	 * @return DataTableCheckboxBuilder
	 */
	public function checked_by_default($checked_by_default) {
		$this->checked_by_default = $checked_by_default;
		return $this;
	}

	/**
	 * @param $placement string Either 'top' or 'bottom'
	 * @return DataTableCheckboxBuilder
	 */
	public function placement($placement) {
		$this->placement = $placement;
		return $this;
	}

	/**
	 * @param $label string HTML
	 * @return DataTableCheckboxBuilder
	 */
	public function label($label) {
		$this->label = $label;
		return $this;
	}

	/**
	 * @return string Field name
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * @return string Checkbox value
	 */
	public function get_value() {
		return $this->value;
	}



	/**
	 * @return string Checked by default?
	 */
	public function get_checked_by_default() {
		return $this->checked_by_default;
	}


	/**
	 * @return string Either 'top' or 'bottom'
	 */
	public function get_placement() {
		return $this->placement;
	}

	/**
	 * @return string HTML label
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * @return DataTableRadio
	 * @throws Exception
	 */
	public function build() {
		if (!is_string($this->name) || trim($this->name) === "") {
			throw new Exception("name must be a non-empty string");
		}

		if (is_null($this->value)) {
			$this->value = "";
		}
		if (!is_string($this->value)) {
			throw new Exception("value must be a string");
		}

		if (is_null($this->checked_by_default)) {
			$this->checked_by_default = false;
		}
		if (!is_bool($this->checked_by_default)) {
			throw new Exception("checked_by_default must be a bool");
		}

		if ($this->placement !== IDataTableWidget::placement_top &&
			$this->placement !== IDataTableWidget::placement_bottom) {
			throw new Exception("placement must be either top or bottom");
		}

		if (is_null($this->label)) {
			$this->label = "";
		}
		if (!is_string($this->label)) {
			throw new Exception("label must be a string");
		}

		return new DataTableRadio($this);
	}


}