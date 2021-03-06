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
	 * @param DataFormState $remote_url
	 * @param $state DataFormState
	 * @return string HTML
	 */
	public function display($form_name, $form_method, $remote_url, $state)
	{
		if ($this->name !== "") {
			$name_array = array($this->name);
		}
		else {
			$name_array = array();
		}
		return self::display_options($form_name, $name_array, $this->form_action, $form_method, $this->change_behavior, $this->options, $this->label, $state);
	}

	/**
	 * Returns HTML for select element
	 *
	 * @param $form_name string Name of form
	 * @param $name_array string[] Name for select. Will become form_name[a1][a2][a3]... for each string in this array
	 * @param $action string URL to submit to when new item is selected
	 * @param $form_method string GET or POST
	 * @param $behavior IDataTableBehavior What happens when new item is selected
	 * @param $options DataTableOption[] Each option for the select element
	 * @param $label string HTML for label
	 * @param $state DataFormState State of form
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
			$qualified_name = DataFormState::make_field_name($form_name, $name_array);

			if ($label !== null && $label !== "") {
				$ret .= '<label for="' . htmlspecialchars($qualified_name) . '">' . $label . '</label>';
			}

			$ret .= '<select id="' . htmlspecialchars($qualified_name) . '" name="' . htmlspecialchars($qualified_name) . '" onchange="' . htmlspecialchars($onchange) . '">';
		}
		else
		{
			$ret .= '<select onchange="' . htmlspecialchars($onchange) . '">';
		}

		// if this item is in state, use whatever value is there, else use the default
		if ($state && $name_array && $state->has_item($name_array)) {
			$has_selected = true;
			$selected = $state->find_item($name_array);
		}
		else
		{
			$has_selected = false;
			$selected = null;
		}

		foreach ($options as $option) {
			if ($has_selected) {
				if ($option->get_value() === $selected) {
					$ret .= $option->display(true);
				}
				else
				{
					$ret .= $option->display(false);
				}
			}
			else {
				$ret .= $option->display($option->is_default_selected());
			}
		}

		$ret .= "</select>";
		return $ret;
	}

}

/**
 * Renders DataTableOptions objects which exist in cell data
 */
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
	 * @throws Exception
	 */
	public function format($form_name, $column_header, $column_data, $rowid, $state) {
		// TODO: form_action and form_method should probably come from somewhere else instead of just using default values
		if (!($column_data instanceof DataTableOptions)) {
			throw new Exception("Only DataTableOptions can be used with DataTableOptionsCellFormatter");
		}
		return DataTableOptions::display_options($form_name, array($column_header, $rowid), "", "POST", null, $column_data->get_options(), "", $state);
	}
}