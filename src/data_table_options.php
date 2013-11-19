<?php

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
	/** @var string  */
	protected $label;

	/**
	 * @param $builder DataTableOptionsBuilder
	 * @throws Exception
	 */
	public function __construct($builder) {
		if (!($builder instanceof DataTableOptionsBuilder)) {
			throw new Exception("builder expected to be instance of DataTableOptionsBuilder");
		}
		$this->options = $builder->get_options();
		$this->name = $builder->get_name();
		$this->form_action = $builder->get_form_action();
		$this->change_behavior = $builder->get_behavior();
		$this->placement = $builder->get_placement();
		$this->label = $builder->get_label();
	}

	public function get_options() {
		return $this->options;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_placement()
	{
		return $this->placement;
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
		return self::display_options($form_name, array($this->name), $this->form_action, $form_method, $this->change_behavior, $this->options, $this->label, $state);
	}

	/**
	 * @param $form_name string
	 * @param $name_array string[] Name for select. Each item will be surrounded by square brackets and concatenated
	 * @param $action string
	 * @param $form_method string GET or POST
	 * @param $behavior IDataTableBehavior
	 * @param $options DataTableOption[]
	 * @param $label string
	 * @param $state DataFormState
	 * @throws Exception
	 * @return string
	 */
	public static function display_options($form_name, $name_array, $action, $form_method, $behavior, $options, $label, $state = null) {
		$ret = "";

		if ($behavior) {
			$onchange = $behavior->action($form_name, $action, $form_method);
		}
		else
		{
			$onchange = "";
		}
		if ($name_array) {
			$qualified_name = $form_name;
			foreach ($name_array as $name) {
				if (strpos($name, "[") !== false || strpos($name, "]") !== false) {
					throw new Exception("No square brackets allowed in name");
				}
				$qualified_name .= "[" . $name . "]";
			}

			if ($label !== null && $label !== "") {
				$ret .= '<label for="' . htmlspecialchars($qualified_name) . '">' . $label . '</label>';
			}

			$ret .= '<select id="' . htmlspecialchars($qualified_name) . '" name="' . htmlspecialchars($qualified_name) . '" onchange="' . htmlspecialchars($onchange) . '">';
		}
		else
		{
			$ret .= '<select onchange="' . htmlspecialchars($onchange) . '">';
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
		return DataTableOptions::display_options($form_name, array($column_header, $rowid), "", "POST", null, $column_data->get_options(), "", $state);
	}
}