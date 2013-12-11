<?php
class DataTableSearchWidgetBuilder
{
	/** @var  string */
	protected $form_action;
	/** @var  IDataTableBehavior */
	protected $behavior;
	/** @var  string */
	protected $placement;
	/** @var  string */
	protected $label;
	/** @var  string */
	protected $search_type;
	/** @var  string */
	protected $table_name;
	/** @var  string */
	protected $column_key;

	/** @var  DataTableSearchState */
	protected $default_value;

	/**
	 * @return DataTableSearchWidgetBuilder
	 */
	public static function create()
	{
		return new DataTableSearchWidgetBuilder();
	}

	/**
	 * @param $form_action string
	 * @return DataTableSearchWidgetBuilder
	 */
	public function form_action($form_action)
	{
		$this->form_action = $form_action;
		return $this;
	}

	/**
	 * @param $behavior IDataTableBehavior
	 * @return DataTableSearchWidgetBuilder
	 */
	public function behavior($behavior)
	{
		$this->behavior = $behavior;
		return $this;
	}

	/**
	 * @param $placement string
	 * @return DataTableSearchWidgetBuilder
	 */
	public function placement($placement)
	{
		$this->placement = $placement;
		return $this;
	}

	/**
	 * @param $label string
	 * @return DataTableSearchWidgetBuilder
	 */
	public function label($label) {
		$this->label = $label;
		return $this;
	}

	/**
	 * @param $search_type string
	 * @return DataTableSearchWidgetBuilder
	 */
	public function search_type($search_type) {
		$this->search_type = $search_type;
		return $this;
	}

	public function table_name($table_name) {
		$this->table_name = $table_name;
		return $this;
	}

	public function column_key($column_key) {
		$this->column_key = $column_key;
		return $this;
	}

	public function default_value($default_value) {
		$this->default_value = $default_value;
		return $this;
	}

	/**
	 * @return string
	 */
	public function get_search_type()
	{
		return $this->search_type;
	}

	/**
	 * @return string
	 */
	public function get_form_action()
	{
		return $this->form_action;
	}

	/**
	 * @return IDataTableBehavior
	 */
	public function get_behavior()
	{
		return $this->behavior;
	}

	/**
	 * @return string
	 */
	public function get_placement()
	{
		return $this->placement;
	}

	/**
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	}

	public function get_table_name() {
		return $this->table_name;
	}

	public function get_column_key() {
		return $this->column_key;
	}

	public function get_default_value() {
		return $this->default_value;
	}

	/**
	 * @return DataTableSearchWidget
	 * @throws Exception
	 */
	public function build()
	{
		if (is_null($this->search_type)) {
			$this->search_type = "";
		}
		$valid_search_types = array(
			DataTableSearchState::equal,
			DataTableSearchState::less_than,
			DataTableSearchState::less_or_equal,
			DataTableSearchState::greater_than,
			DataTableSearchState::greater_or_equal,
			DataTableSearchState::rlike,
			DataTableSearchState::like
		);
		if (!in_array($this->search_type, $valid_search_types)) {
			throw new Exception("search_type must be a string, one of the constants defined in DataTableSearchState");
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

		if (is_null($this->label)) {
			$this->label = "";
		}
		if (!is_string($this->label)) {
			throw new Exception("label must be a string or null");
		}

		if (!is_null($this->default_value) && !($this->default_value instanceof DataTableSearchState)) {
			throw new Exception("default_value must be instance of DataTableSearchState");
		}

		if (!is_string($this->column_key) || trim($this->column_key) === "") {
			throw new Exception("column key must be specified");
		}

		if (is_null($this->table_name)) {
			$this->table_name = "";
		}
		if (!is_string($this->table_name)) {
			throw new Exception("table name must be a string");
		}

		return new DataTableSearchWidget($this);
	}
}
