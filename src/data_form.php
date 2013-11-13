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

	/** @var  string Form method, either GET or POST */
	private $method;

	/** @var  string CSS class for div */
	private $div_class;
	/** @var string|bool Either false or a URL to send pagination, sorting, or searching requests to */
	private $remote;


	/**
	 * @param $builder DataFormBuilder
	 * @throws Exception
	 */
	public function __construct($builder) {
		if (!($builder instanceof DataFormBuilder)) {
			throw new Exception("builder expected to be instance of DataFormBuilder");
		}
		$this->tables = $builder->get_tables();
		$this->form_name = $builder->get_form_name();
		$this->forwarded_state = $builder->get_forwarded_state();
		$this->method = $builder->get_method();
		$this->div_class = $builder->get_div_class();
		$this->remote = $builder->get_remote();
	}

	/**
	 * Returns HTML for a div wrapping a form
	 * @param DataFormState $state
	 * @throws Exception
	 * @return string HTML
	 */
	public function display($state=null) {
		if ($state && !($state instanceof DataFormState)) {
			throw new Exception("state must be instance of DataFormState");
		}
		if (!$state && $this->remote) {
			throw new Exception("If form is a remote form, state must be specified");
		}

		$ret =  '<div class="' . htmlspecialchars($this->div_class) . '" id="' . htmlspecialchars($this->form_name) . '">';
		$ret .=  $this->display_form($state);
		$ret .= '</div>';
		return $ret;
	}

	public function display_form($state=null) {
		if ($state && !($state instanceof DataFormState)) {
			throw new Exception("state must be instance of DataFormState");
		}
		if (!$state && $this->remote) {
			throw new Exception("If form is a remote form, state must be specified");
		}
		$ret = "";

		// form action is set in javascript
		$ret .= '<form name="' . htmlspecialchars($this->form_name) . '" method="' . htmlspecialchars($this->method) . '">';

		$field_name = DataFormState::make_field_name($this->form_name, DataFormState::exists_key());
		$ret .= '<input type="hidden" name="' . htmlspecialchars($field_name) . '" value="true" />';

		foreach ($this->forwarded_state as $forwarded_state) {
			$ret .= self::make_inputs_from_forwarded_state($forwarded_state->get_form_data(), $this->form_name . "[" . DataFormState::forwarded_state_key . "][" . $forwarded_state->get_form_name() . "]");
		}

		foreach ($this->tables as $table) {
			$ret .= $table->display_table($this->form_name, $this->method, $this->remote, $state);
		}
		$ret .= "</form>";

		return $ret;
	}

	/**
	 * Writes a bunch of hidden inputs for $obj. Names are concatenated such that a[b][c] will be stored
	 * in $_POST or $_GET as {'a' : {'b' : {'c' : value}}}
	 *
	 * @param $obj array|string|number either an array with more hidden inputs, or something convertable to a string
	 * @param $base string Prefix for input name
	 * @throws Exception
	 * @return string HTML of hidden inputs
	 */
	private static function make_inputs_from_forwarded_state($obj, $base)
	{
		if (!$base) {
			throw new Exception("base must not be empty");
		}
		if (!is_string($base)) {
			throw new Exception("base must be a string");
		}

		if (is_array($obj)) {
			$ret = "";
			foreach ($obj as $k => $v) {
				$ret .= self::make_inputs_from_forwarded_state($obj[$k], $base . "[" . $k . "]");
			}
		}
		else
		{
			$ret = '<input type="hidden" name="' . htmlspecialchars($base) . '" value="' . htmlspecialchars($obj) . '" />';
		}
		return $ret;
	}

}