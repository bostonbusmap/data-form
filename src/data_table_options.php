<?php
class DataTableOption {
	/** @var  string */
	protected $text;
	/** @var  string */
	protected $value;
	/** @var  bool */
	protected $selected;

	public function __construct($text, $value, $selected=false) {
		$this->text = $text;
		if (is_int($value)) {
			$value = (string)$value;
		}
		$this->value = $value;
		$this->selected = $selected;
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

	/**
	 * @param $override_select bool|null Either override with true or false, or null if no override
	 * @return string HTML
	 */
	public function display($override_select) {
		$value = $this->value;
		$text = $this->text;
		$selected = $this->selected;

		if (!is_null($override_select)) {
			$selected = $override_select;
		}
		if ($selected) {
			return '<option value="' . htmlspecialchars($value) . '" selected>' . $text . "</option>";
		}
		else
		{
			return '<option value="' . htmlspecialchars($value) . '">' . $text . "</option>";
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
	 * @param $options
	 * @param $name
	 * @param $form_action string Optional. URL of form to submit to on change.
	 * @param $change_behavior IDataTableBehavior Optional. What happens when item is changed
	 * @param string $placement string Optional. Where to display options relative to the table, either 'top' or 'bottom'
	 * @throws Exception
	 */
	public function __construct($options, $name, $form_action, $change_behavior = null, $placement = self::placement_top) {
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
	 * @param $form_method string Either GET or POST
	 * @param $state DataFormState
	 * @return string HTML
	 */
	public function display($form_name, $form_method, $state=null)
	{
		return self::display_options($form_name, array($this->name), $this->form_action, $form_method, $this->change_behavior, $this->options, $state);
	}

	/**
	 * @param $form_name string
	 * @param $name_array string[] Name for select. Each item will be surrounded by square brackets and concatenated
	 * @param $action string
	 * @param $form_method string GET or POST
	 * @param $behavior IDataTableBehavior
	 * @param $options DataTableOption[]
	 * @param $state DataFormState
	 * @return string
	 */
	public static function display_options($form_name, $name_array, $action, $form_method, $behavior, $options, $state=null) {
		if ($action && $behavior) {
			$onchange = $behavior->action($form_name, $action, $form_method);
		}
		else
		{
			$onchange = "";
		}
		if ($name_array) {
			$qualified_name = $form_name;
			foreach ($name_array as $name) {
				// TODO: sanitize
				$qualified_name .= "[" . $name . "]";
			}

			$ret = '<select name="' . htmlspecialchars($qualified_name) . '" onchange="' . htmlspecialchars($onchange) . '">';
		}
		else
		{
			$ret = '<select onchange="' . htmlspecialchars($onchange) . '">';
		}

		if ($name_array && $state) {
			$selected_item = $state->find_item($name_array);
		}
		else
		{
			$selected_item = null;
		}
		foreach ($options as $option) {
			if (is_null($selected_item)) {
				$override_select = null;
			}
			elseif ($selected_item === $option->get_value())
			{
				$override_select = true;
			}
			else
			{
				$override_select = false;
			}

			$ret .= $option->display($override_select);
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
	 * @param string $rowid Row id number
	 * @param DataFormState $state State of form
	 * @return string HTML for a link
	 */
	public function format($form_name, $column_header, $column_data, $rowid, $state) {
		return DataTableOptions::display_options($form_name, array($column_header, $rowid), "", "POST", null, $column_data->get_options(), $state);
	}
}