<?php
require_once "data_table_widget.php";

class DataTableOptionsBuilder {
	/** @var DataTableOption[] */
	protected $options;
	/** @var  string Name of form element */
	protected $name;
	/** @var  string */
	protected $form_action;
	/** @var  IDataTableBehavior */
	protected $behavior;
	/** @var  string */
	protected $placement;
	/** @var  string */
	protected $label;

	/**
	 * @return DataTableOptionsBuilder
	 */
	public static function create() {
		return new DataTableOptionsBuilder();
	}

	/**
	 * @param $options DataTableOption[]
	 * @return DataTableOptionsBuilder
	 */
	public function options($options) {
		$this->options = $options;
		return $this;
	}

	/**
	 * @param $name string
	 * @return DataTableOptionsBuilder
	 */
	public function name($name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * @param $form_action string
	 * @return DataTableOptionsBuilder
	 */
	public function form_action($form_action) {
		$this->form_action = $form_action;
		return $this;
	}

	/**
	 * @param $behavior string
	 * @return DataTableOptionsBuilder
	 */
	public function behavior($behavior) {
		$this->behavior = $behavior;
		return $this;
	}

	/**
	 * @param $placement string
	 * @return DataTableOptionsBuilder
	 */
	public function placement($placement) {
		$this->placement = $placement;
		return $this;
	}

	/**
	 * @param $label string
	 * @return DataTableOptionsBuilder
	 */
	public function label($label) {
		$this->label = $label;
		return $this;
	}

	/**
	 * @return DataTableOption[]
	 */
	public function get_options() {
		return $this->options;
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
	 * @return DataTableOptions
	 * @throws Exception
	 */
	public function build() {
		if (is_null($this->name)) {
			$this->name = "";
		}
		if (!is_string($this->name)) {
			throw new Exception("name must be a string");
		}

		if (is_null($this->options)) {
			$this->options = array();
		}
		if (!is_array($this->options)) {
			throw new Exception("options must be an array of DataTableOption");
		}
		foreach ($this->options as $option) {
			if (!($option instanceof DataTableOption)) {
				throw new Exception("Each item in options must be of type DataTableOption");
			}
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

		return new DataTableOptions($this);
	}
}