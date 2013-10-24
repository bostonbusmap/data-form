<?php
class DataTableOption {
	/** @var  string */
	protected $text;
	/** @var  string */
	protected $value;
	/** @var  bool */
	protected $selected;

	public function __construct($text, $value, $is_selected) {
		$this->text = $text;
		$this->value = $value;
		$this->is_selected = $is_selected;
	}

	public function get_text() {
		return $this->text;
	}

	public function get_value() {
		return $this->value;
	}

	public function is_selected() {
		return $this->selected;
	}

	public function display() {
		$value = $this->value;
		$text = $this->text;
		if ($this->selected) {
			return "<option value='$value' selected>$text</option>";
		}
		else
		{
			return "<option value='$value'>$text</option>";
		}
	}
}

/**
 * Formats for the HTML select element. To use, create a DataTableOptions object for each cell in the column.
 * Create a DataTableOption object for each option in the select element.
 * Use DataTableOptionsCellFormatter as an option for the DataTableColumn to display the element
 */
class DataTableOptions implements IDataTableWidget {
	/** @var DataTableOption[] */
	protected $options;
	/** @var  string Name of form element */
	protected $name;
	/** @var  string */
	protected $form_action;
	/** @var  IDataTableBehavior */
	protected $change_behavior;
	/** @var  string */
	protected $placement;

	/**
	 * @param $name
	 * @param $options
	 * @param $form_action string Optional. URL of form to submit to on change.
	 * @param $change_behavior IDataTableBehavior Optional. What happens when item is changed
	 * @param string $placement string Optional. Where to display options relative to the table, either 'top' or 'bottom'
	 * @throws Exception
	 */
	public function __construct($name, $options, $form_action="", $change_behavior=null, $placement=self::placement_top) {
		$this->name = $name;
		if (!$this->options) {
			$this->options = array();
		}
		$this->options = $options;
		$this->form_action = $form_action;
		$this->change_behavior = $change_behavior;
		if ($placement != self::placement_top && $placement != self::placement_bottom) {
			throw new Exception("placement must be 'top' or 'bottom'");
		}
		$this->placement = $placement;
	}

	public function get_options() {
		return $this->options;
	}

	public function get_name() {
		return $this->name;
	}


	/**
	 * Displays options for a form. To display options for a particular cell use DataTableOptionsCellFormatter
	 *
	 * @param $form_name string Name of form
	 * @return string HTML
	 */
	public function display($form_name)
	{
		return self::display_options($form_name, $this->name, $this->form_action, $this->change_behavior, $this->options);
	}

	/**
	 * @param $form_name string
	 * @param $select_name string
	 * @param $action string
	 * @param $behavior IDataTableBehavior
	 * @param $options DataTableOption[]
	 * @return string
	 */
	public static function display_options($form_name, $select_name, $action, $behavior, $options) {
		if ($action && $behavior) {
			$onchange = $behavior->action($form_name, $action);
		}
		else
		{
			$onchange = "";
		}
		if ($select_name) {
			$qualified_name = $form_name . "[" . $select_name . "]";
			$ret = "<select name='$qualified_name' onchange='$onchange'>";
		}
		else
		{
			$ret = "<select onchange='$onchange'>";
		}

		foreach ($options as $option) {
			$ret .= $option->display();
		}

		$ret .= "</select>";
		return $ret;
	}

	public function get_placement()
	{
		return $this->placement;
	}
}

class DataTableOptionsCellFormatter implements IDataTableCellFormatter {
	/**
	 * Implementation to display a checkbox
	 *
	 * @param string $form_name The name of the form
	 * @param string $column_header Unused
	 * @param DataTableOptions $column_data The link data
	 * @param int $rowid Row id number
	 * @return string HTML for a link
	 */
	public function format($form_name, $column_header, $column_data, $rowid) {
		return DataTableOptions::display_options($form_name, $column_header, "", null, $column_data->get_options());
	}
}