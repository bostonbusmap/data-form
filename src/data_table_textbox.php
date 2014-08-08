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
 * Represents a simple one line textbox
 */
class DataTableTextbox implements IDataTableWidget {
	/** @var  string Default text */
	protected $text;
	/** @var  string Field name (becomes form_name[name]) */
	protected $name;
	/** @var  string URL to submit to */
	protected $action;
	/** @var  IDataTableBehavior What happens when Enter is pressed */
	protected $submit_behavior;
	/** @var string either 'top' or 'bottom' */
	protected $placement;
	/** @var string HTML for label. May be blank if no label */
	protected $label;

	/**
	 * Use DataTableTextboxBuilder::build()
	 *
	 * @param $builder DataTableTextboxBuilder
	 * @throws Exception
	 */
	public function __construct($builder) {
		if (!($builder instanceof DataTableTextboxBuilder)) {
			throw new Exception("builder expected to be instance of DataTableTextboxBuilder");
		}
		$this->text = $builder->get_text();
		$this->name = $builder->get_name();
		$this->action = $builder->get_form_action();
		$this->submit_behavior = $builder->get_behavior();
		$this->placement = $builder->get_placement();
		$this->label = $builder->get_label();
	}

	public function display($form_name, $form_method, $state)
	{
		if ($this->name !== "") {
			$name_array = array($this->name);
		}
		else
		{
			$name_array = array();
		}
		return self::display_textbox($form_name, $name_array, $this->action, $form_method, $this->submit_behavior, $this->text, $this->label, $state);
	}

	public function get_placement()
	{
		return $this->placement;
	}

	/**
	 * Display textbox HTML
	 *
	 * @param $form_name string Name of form
	 * @param $name_array string[] Name for textarea. Each item will be surrounded by square brackets and concatenated
	 * @param $action string URL to submit to
	 * @param $form_method string GET or POST
	 * @param $behavior IDataTableBehavior What happens when enter is pressed
	 * @param $default_text string Default text for textbox
	 * @param $label string Label HTML for textbox
	 * @param $state DataFormState State for form
	 * @return string HTML
	 */
	public static function display_textbox($form_name, $name_array, $action, $form_method, $behavior, $default_text, $label, $state = null) {
		$ret = "";

		if ($behavior) {
			// trigger if user presses Enter
			$onchange = "if (event.keyCode == 13) { " . $behavior->action($form_name, $action, $form_method) . " }";
		}
		else
		{
			$onchange = "";
		}

		if ($name_array && $state && $state->has_item($name_array)) {
			$text = $state->find_item($name_array);
		}
		else
		{
			$text = $default_text;
		}

		if ($text === null) {
			$text = "";
		}
		if (!is_string($text)) {
			$text = (string)$text;
		}

		if ($name_array) {
			$qualified_name = DataFormState::make_field_name($form_name, $name_array);

			if ($label !== null && $label !== "") {
				$ret .= '<label for="' . htmlspecialchars($qualified_name) . '">' . $label . '</label>';
			}

			$ret .= '<input type="text" id="' . htmlspecialchars($qualified_name) . '" name="' . htmlspecialchars($qualified_name) . '" onkeypress="' . htmlspecialchars($onchange) . '" value="' . htmlspecialchars($text) . '" />';
		}
		else
		{
			$ret .= '<input type="text" onkeypress="' . htmlspecialchars($onchange) . '" value="' . htmlspecialchars($text) . '" />';
		}

		return $ret;
	}
}

/**
 * Puts column data in a textbox for each row in the table
 *
 * Be careful not to use this at the same time as the textbox header formatter on the same column
 * since they will both use the same name and overwrite each other.
 */
class DataTableTextboxCellFormatter implements IDataTableCellFormatter {
	public function format($form_name, $column_header, $column_data, $rowid, $state)
	{
		return DataTableTextbox::display_textbox($form_name, array($column_header, $rowid), "", "POST", null, $column_data, "", $state);
	}
}

/**
 * Puts header data in a textbox. Useful if you want to allow user to edit column titles
 *
 * Be careful not to use this at the same time as the textbox cell formatter on the same column
 * since they will both use the same name and overwrite each other.
 */
class DataTableTextboxHeaderFormatter implements IDataTableHeaderFormatter {
	public function format($form_name, $column_key, $header_data, $state)
	{
		return DataTableTextbox::display_textbox($form_name, array($column_key), "", "POST", null, $header_data, "", $state);
	}
}