<?php

require_once "data_table_widget.php";

/**
 * A widget which displays a button for submitting a DataTable form or resetting it
 */
class DataTableButton implements IDataTableWidget {
	/** @var string  */
	private $text;
	/**
	 * @var string
	 */
	private $action;
	/** @var  string */
	private $name;
	/** @var  string */
	private $type;
	/**
	 * @var IDataTableBehavior
	 */
	private $behavior;

	/**
	 * @var string
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
		$this->placement = $builder->get_placement();
		$this->label = $builder->get_label();
	}

	/**
	 * @param $form_name string
	 * @param $name_array string[] Name for button. Each item will be surrounded by square brackets and concatenated
	 * @param $action string
	 * @param $form_method string GET or POST
	 * @param $behavior IDataTableBehavior
	 * @param $text string
	 * @param $type string Type of button (currently either 'submit' or 'reset')
	 * @param $label string
	 * @param $state DataFormState
	 * @return string
	 */
	public static function display_button($form_name, $name_array, $action, $form_method, $behavior, $text, $type, $label, $state = null) {
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

			$ret .= '<input type="' . htmlspecialchars($type) . '" id="' . htmlspecialchars($qualified_name) .
				'" name="' . htmlspecialchars($qualified_name) . '" value="' . htmlspecialchars($text) .
				'" onclick="' . htmlspecialchars($onchange) . '"/>';
		}
		else
		{
			$ret .= '<input type="' . htmlspecialchars($type) . '" value="' . htmlspecialchars($text) .
				'" onclick="' . htmlspecialchars($onchange) . '"/>';
		}

		$ret .= "</select>";
		return $ret;
	}

	public function display($form_name, $form_method, $state=null)
	{
		return self::display_button($form_name, array($this->name), $this->action, $form_method, $this->behavior, $this->text, $this->type, $this->label, $state);
	}

	public function get_placement() {
		return $this->placement;
	}
}