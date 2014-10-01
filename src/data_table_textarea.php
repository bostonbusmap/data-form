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
 * Represents a simple multi line textarea
 */
class DataTableTextarea implements IDataTableWidget {
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
	 * Use DataTableTextareaBuilder::build()
	 *
	 * @param $builder DataTableTextareaBuilder
	 * @throws Exception
	 */
	public function __construct($builder) {
		if (!($builder instanceof DataTableTextareaBuilder)) {
			throw new Exception("builder expected to be instance of DataTableTextareaBuilder");
		}
		$this->text = $builder->get_text();
		$this->name = $builder->get_name();
		$this->action = $builder->get_form_action();
		$this->submit_behavior = $builder->get_behavior();
		$this->placement = $builder->get_placement();
		$this->label = $builder->get_label();
	}

	public function display($form_name, $form_method, $remote_url, $state)
	{
		if ($this->name !== "") {
			$name_array = array($this->name);
		}
		else
		{
			$name_array = array();
		}
		return self::display_textarea($form_name, $name_array, $this->action, $form_method, $this->submit_behavior, $this->text, $this->label, $state);
	}

	public function get_placement()
	{
		return $this->placement;
	}

	/**
	 * Display textarea HTML
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
	public static function display_textarea($form_name, $name_array, $action, $form_method, $behavior, $default_text, $label, $state = null) {
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

		if ($name_array) {
			$qualified_name = DataFormState::make_field_name($form_name, $name_array);

			if ($label !== null && $label !== "") {
				$ret .= '<label for="' . htmlspecialchars($qualified_name) . '">' . $label . '</label>';
			}

			$ret .= '<textarea id="' . htmlspecialchars($qualified_name) . '" name="' . htmlspecialchars($qualified_name) . '" onkeypress="' . htmlspecialchars($onchange) . '">' . htmlspecialchars($text) . '</textarea>';
		}
		else
		{
			$ret .= '<textarea onkeypress="' . htmlspecialchars($onchange) . '">' . htmlspecialchars($text) . '</textarea';
		}

		return $ret;
	}
}
