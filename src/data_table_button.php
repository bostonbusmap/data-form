<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

require_once "data_table_widget.php";

/**
 * A widget which displays a HTML button
 */
class DataTableButton implements IDataTableWidget {
	/** @var string Text of button  */
	private $text;
	/**
	 * @var string URL of button
	 */
	private $action;
	/** @var  string field name (will be altered to form_name[name]) */
	private $name;
	/**
	 * @var string Value to be submitted with form
	 */
	private $value;
	/** @var  string Either 'submit' or 'reset' */
	private $type;
	/**
	 * @var IDataTableBehavior What happens when button is clicked
	 */
	private $behavior;

	/**
	 * @var string Where button will go relative to form
	 */
	private $placement;
	/**
	 * @var string HTML for label
	 */
	private $label;

	/**
	 * @param $builder DataTableButtonBuilder
	 * @throws Exception
	 */
	public function __construct($builder) {
		if (!($builder instanceof DataTableButtonBuilder)) {
			throw new Exception("builder expected to be instance of DataTableButtonBuilder");
		}
		$this->text = $builder->get_text();
		$this->type = $builder->get_type();
		$this->action = $builder->get_form_action();
		$this->behavior = $builder->get_behavior();
		$this->name = $builder->get_name();
		$this->value = $builder->get_value();
		$this->placement = $builder->get_placement();
		$this->label = $builder->get_label();
	}

	/**
	 * Returns HTML for button
	 *
	 * @param $form_name string Name of form
	 * @param $name_array string[] Name for button. Each item will be surrounded by square brackets and concatenated
	 * @param $action string URL to submit form to
	 * @param $form_method string GET or POST
	 * @param $behavior IDataTableBehavior What happens when button is clicked
	 * @param $text string Text of button
	 * @param $type string Type of button (currently either 'submit' or 'reset')
	 * @param $label string Label HTML for button
	 * @param $value string Value for button
	 * @param $state DataFormState
	 * @return string HTML
	 */
	public static function display_button($form_name, $name_array, $action, $form_method, $behavior, $text, $type, $label, $value, $state) {
		$ret = "";

		if ($behavior) {
			$onchange = '';
			if ($name_array) {
				$qualified_name = DataFormState::make_field_name($form_name, $name_array);
				$onchange = '$(DataForm.jq(' . json_encode($qualified_name . '_hidden') . ')).attr("value", ' . json_encode($value) . "); ";
			}

			$onchange .= $behavior->action($form_name, $action, $form_method);
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

			$ret .= '<button type="' . htmlspecialchars($type) . '" id="' . htmlspecialchars($qualified_name . "_button") .
				'" name="' . htmlspecialchars($qualified_name) . '" onclick="' . htmlspecialchars($onchange) . '">'
				. htmlspecialchars($text) . '</button>';

			if ($value !== '') {
				$ret .= DataTableHidden::display_hidden($form_name, $name_array, $qualified_name . "_hidden", '', "hidden_submit");
			}
		}
		else
		{
			$ret .= '<button type="' . htmlspecialchars($type) . '" onclick="' . htmlspecialchars($onchange) . '">'
				. htmlspecialchars($text) . '</button>';
		}

		return $ret;
	}

	public function display($form_name, $form_method, $remote_url, $state)
	{
		if ($this->name) {
			$name_array = array($this->name);
		}
		else
		{
			$name_array = array();
		}
		return self::display_button($form_name, $name_array, $this->action, $form_method, $this->behavior, $this->text, $this->type, $this->label, $this->value, $state);
	}

	public function get_placement() {
		return $this->placement;
	}
}