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
 * Builder for DataTableButton
 */
class DataTableButtonBuilder {
	/** @var string Text of button */
	protected $text;
	/**
	 * @var string Type of button, either 'submit' or 'reset'
	 */
	protected $type;
	/** @var  string Name of form element */
	protected $name;
	/** @var  string URL to submit to */
	protected $form_action;
	/** @var  IDataTableBehavior What happens when button is clicked */
	protected $behavior;
	/** @var  string Where button will be displayed relative to form */
	protected $placement;
	/**
	 * @var string Label for button
	 */
	protected $label;

	/**
	 * Creates a builder. Identical to constructor
	 * @return DataTableButtonBuilder
	 */
	public static function create() {
		return new DataTableButtonBuilder();
	}

	/**
	 * @param $text string
	 * @return DataTableButtonBuilder
	 */
	public function text($text) {
		$this->text = $text;
		return $this;
	}

	/**
	 * @param $type string
	 * @return DataTableButtonBuilder
	 */
	public function type($type) {
		$this->type = $type;
		return $this;
	}

	/**
	 * @param $name string
	 * @return DataTableButtonBuilder
	 */
	public function name($name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * @param $form_action string
	 * @return DataTableButtonBuilder
	 */
	public function form_action($form_action) {
		$this->form_action = $form_action;
		return $this;
	}

	/**
	 * @param $behavior IDataTableBehavior
	 * @return DataTableButtonBuilder
	 */
	public function behavior($behavior) {
		$this->behavior = $behavior;
		return $this;
	}

	/**
	 * @param $placement string
	 * @return DataTableButtonBuilder
	 */
	public function placement($placement) {
		$this->placement = $placement;
		return $this;
	}

	/**
	 * @param $label string
	 * @return DataTableButtonBuilder
	 */
	public function label($label) {
		$this->label = $label;
		return $this;
	}

	/**
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function get_text() {
		return $this->text;
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function get_form_action() {
		return $this->form_action;
	}

	/**
	 * @return IDataTableBehavior
	 */
	public function get_behavior() {
		return $this->behavior;
	}

	/**
	 * @return string
	 */
	public function get_placement() {
		return $this->placement;
	}

	/**
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * Validates input and creates DataTableButton
	 *
	 * @return DataTableButton
	 * @throws Exception
	 */
	public function build() {
		if (is_null($this->name)) {
			$this->name = "";
		}
		if (!is_string($this->name)) {
			throw new Exception("name must be a string");
		}

		if (is_null($this->text)) {
			$this->text = "";
		}
		if (!is_string($this->text)) {
			throw new Exception("text must be a string");
		}

		if (is_null($this->type)) {
			$this->type = "submit";
		}
		if (!is_string($this->type)) {
			throw new Exception("type must be a string");
		}

		if ($this->form_action && !is_string($this->form_action)) {
			throw new Exception("form_action must be a string");
		}
		if ($this->behavior && !($this->behavior instanceof IDataTableBehavior)) {
			throw new Exception("change_behavior must be instance of IDataTableBehavior");
		}
		if (is_null($this->placement)) {
			$this->placement = IDataTableWidget::placement_top;
		}
		if ($this->placement != IDataTableWidget::placement_top && $this->placement != IDataTableWidget::placement_bottom) {
			throw new Exception("placement must be 'top' or 'bottom'");
		}
		if (!is_null($this->label) && !is_string($this->label)) {
			throw new Exception("label must be a string or null");
		}

		return new DataTableButton($this);
	}
}