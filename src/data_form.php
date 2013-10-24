<?php
require_once "data_form_builder.php";
require_once "data_table.php";

class DataForm {
	/** @var  DataTable[] */
	protected $tables;

	/** @var string */
	private $form_name;


	/** @var  DataFormState[] State received which should be forwarded */
	private $forwarded_state;

	/**
	 * @param $builder DataFormBuilder
	 */
	public function __construct($builder) {
		$this->tables = $builder->get_tables();
		$this->form_name = $builder->get_form_name();
		$this->forwarded_state = $builder->get_forwarded_state();
	}

	/**
	 * Returns HTML for a div wrapping a form
	 * @param DataFormState $state
	 * @return string HTML
	 */
	public function display($state) {
		$ret =  "<div id='" . $this->form_name . "'>";
		$ret .=  $this->display_form($state);
		$ret .= "</div>";
		return $ret;
	}

	public function display_form($state) {
		$ret = "";

		// form action is set in javascript
		$ret .= "<form name='" . $this->form_name . "' method='post'>";

		foreach ($this->forwarded_state as $state) {
			$ret .= self::make_inputs_from_state($state->get_form_data(), $this->form_name . "[" . DataFormState::forwarded_state_key . "][" . $state->get_form_name() . "]");
		}

		foreach ($this->tables as $table) {
			$ret .= $table->display_table($state, $this->form_name);
		}
		$ret .= "</form>";

		return $ret;
	}

	private static function make_inputs_from_state($obj, $base)
	{
		// TODO: sanitize HTML
		if (is_array($obj)) {
			$ret = "";
			foreach ($obj as $k => $v) {
				$ret .= self::make_inputs_from_state($obj[$k], $base . "[" . $k . "]");
			}
		}
		else
		{
			$ret = "<input type='hidden' name='" . $base . "' value='" . $obj . "' />";
		}
		return $ret;
	}

}