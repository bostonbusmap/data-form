<?php
class DataTableTextbox implements IDataTableWidget {
	/** @var  string */
	protected $text;
	/** @var  string */
	protected $name;
	/** @var  string URL to submit to */
	protected $action;
	/** @var  IDataTableBehavior */
	protected $submit_behavior;
	/** @var string either 'top' or 'bottom' */
	protected $placement;

	public function __construct($text, $name, $form_action, $submit_behavior = null, $placement = self::placement_top) {
		$this->text = $text;
		$this->name = $name;
		$this->action = $form_action;
		$this->submit_behavior = $submit_behavior;
		$this->placement = $placement;

	}

	public function display($form_name, $state)
	{
		return self::display_textbox($form_name, array($this->name), $this->action, $this->submit_behavior, $this->text, $state);
	}

	public function get_placement()
	{
		return $this->placement;
	}

	/**
	 * @param $form_name string
	 * @param $name_array string[] Name for select. Each item will be surrounded by square brackets and concatenated
	 * @param $action string
	 * @param $behavior IDataTableBehavior
	 * @param $default_text string
	 * @param $state DataFormState
	 * @return string
	 */
	public static function display_textbox($form_name, $name_array, $action, $behavior, $default_text, $state=null) {
		if ($action && $behavior) {
			$onchange = $behavior->action($form_name, $action);
		}
		else
		{
			$onchange = "";
		}

		if ($name_array && $state) {
			$text = $state->find_item($name_array);
		}
		else
		{
			$text = $default_text;
		}

		if ($name_array) {
			$qualified_name = $form_name;
			foreach ($name_array as $name) {
				// TODO: sanitize
				$qualified_name .= "[" . $name . "]";
			}

			$ret = "<input type='text' name='$qualified_name' onsubmit='$onchange' value='$text' />";
		}
		else
		{
			$ret = "<input type='text' onsubmit='$onchange' value='$text' />";
		}

		$ret .= "</select>";
		return $ret;
	}
}

class DataTableTextboxCellFormatter implements IDataTableCellFormatter {
	public function format($form_name, $column_header, $column_data, $rowid, $state)
	{
		return DataTableTextbox::display_textbox($form_name, array($column_header, $rowid), "", null, $column_data, $state);
	}
}