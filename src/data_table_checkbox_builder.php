<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */
class DataTableCheckboxBuilder {
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
	 * @var IDataTableBehavior What happens when checkbox is clicked
	 */
	protected $behavior;
	/**
	 * @var string Either 'top' or 'bottom'
	 */
	protected $placement;
	/**
	 * @var string URL for behavior's action
	 */
	protected $form_action;

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
	 * @param $behavior IDataTableBehavior What happens when checkbox is clicked
	 * @return DataTableCheckboxBuilder
	 */
	public function behavior($behavior) {
		$this->behavior = $behavior;
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
	 * @var string URL for behavior's action
	 * @return DataTableCheckboxBuilder
	 */
	public function form_action($form_action) {
		$this->form_action = $form_action;
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
	 * @return string What happens when checkbox is clicked
	 */
	public function get_behavior() {
		return $this->behavior;
	}


	/**
	 * @return string Either 'top' or 'bottom'
	 */
	public function get_placement() {
		return $this->placement;
	}
	/**
	 * @return string URL for behavior's action
	 */
	public function get_form_action() {
		return $this->form_action;
	}


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

		if (!($this->behavior instanceof IDataTableBehavior)) {
			throw new Exception("behavior must be type of IDataTableBehavior");
		}

		if ($this->placement !== IDataTableWidget::placement_top &&
			$this->placement !== IDataTableWidget::placement_bottom) {
			throw new Exception("placement must be either top or bottom");
		}

		if (is_null($this->form_action)) {
			$this->form_action = "";
		}
		if (!is_string($this->form_action)) {
			throw new Exception("form action must be string");
		}

		return new DataTableCheckbox($this);
	}


}