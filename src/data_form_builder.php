<?php
class DataFormBuilder {
	/** @var  DataTable[] */
	protected $tables;

	/** @var string */
	private $form_name;


	/** @var  DataFormState[] State received which should be forwarded */
	private $forwarded_state;

	/** @var  string Form submit method, either POST or GET. POST by default */
	private $method;

	/** @var  string CSS class for div */
	private $div_class;

	/** @var string|bool If this is a string then sorting, searching, and pagination options are sent to this URL
	 * If false sorting and searching are done locally */
	private $remote;

	/** @var IValidatorRule[] */
	private $validator_rules;


	public function __construct($form_name) {
		$this->form_name = $form_name;
	}

	/**
	 * @param $form_name string
	 * @return DataFormBuilder
	 */
	public static function create($form_name) {
		return new DataFormBuilder($form_name);
	}

	/**
	 * @param $tables DataTable[]
	 * @return DataFormBuilder
	 */
	public function tables($tables) {
		$this->tables = $tables;
		return $this;
	}

	/**
	 * A list of states from previous pages which you want to keep when submitting to the next page
	 * or refreshing from this one.
	 *
	 * @param $forwarded_state DataFormState[]
	 * @return DataFormBuilder
	 */
	public function forwarded_state($forwarded_state) {
		$this->forwarded_state = $forwarded_state;
		return $this;
	}

	/**
	 * @param $method string
	 * @return DataFormBuilder
	 */
	public function method($method) {
		$this->method = $method;
		return $this;
	}

	/**
	 * @param $div_class string
	 * @return DataFormBuilder
	 */
	public function div_class($div_class) {
		$this->div_class = $div_class;
		return $this;
	}

	/**
	 * If this is a string then sorting, searching, and pagination options are sent to this URL
	 * If false sorting and searching are done locally
	 *
	 * @param bool|string $remote
	 * @return DataFormBuilder
	 */
	public function remote($remote)
	{
		$this->remote = $remote;
		return $this;
	}

	public function validator_rules($validator_rules) {
		$this->validator_rules = $validator_rules;
		return $this;
	}



	/**
	 * @throws Exception
	 * @return DataForm
	 */
	public function build() {
		if (!is_string($this->form_name)) {
			throw new Exception("form_name must be a string");
		}
		if (trim($this->form_name) === "") {
			throw new Exception("form_name is blank");
		}
		if (strpos($this->form_name, ".") !== false ||
			strpos($this->form_name, " ") !== false ||
			strpos($this->form_name, ":") !== false) {
			throw new Exception("Illegal character in form_name");
		}

		if (is_null($this->forwarded_state)) {
			$this->forwarded_state = array();
		}
		if (!is_array($this->forwarded_state)) {
			throw new Exception("forwarded_state must be an array of DataFormState");
		}
		foreach ($this->forwarded_state as $state) {
			if (!($state instanceof DataFormState)) {
				throw new Exception("forwarded_state must contain instances of DataFormState");
			}
		}

		if (is_null($this->method)) {
			$this->method = "POST";
		}
		if (!is_string($this->method)) {
			throw new Exception("method must be a string, either GET or POST");
		}
		if (strtolower($this->method) != "get" && strtolower($this->method) != "post") {
			throw new Exception("method must be GET or POST");
		}

		if (is_null($this->tables)) {
			$this->tables = array();
		}
		if (!is_array($this->tables)) {
			throw new Exception("tables must be an array of DataTable objects");
		}
		foreach ($this->tables as $table) {
			if (!($table instanceof DataTable)) {
				throw new Exception("Each item in tables must be a DataTable object");
			}
		}
		if (count($this->tables) > 1) {
			$names = array();
			foreach ($this->tables as $table) {
				/** @var $table DataTable */
				$table_name = $table->get_table_name();
				if (!is_string($table_name)) {
					throw new Exception("table names must be strings");
				}
				if (trim($table_name) === "") {
					throw new Exception("Each table must have a name if there is more than one in a form");
				}
				if (in_array($table_name, $names)) {
					throw new Exception("Each table must have a unique name. Found duplicate for '$table_name'");
				}
				$names[] = $table_name;
			}
		}

		if (is_null($this->div_class)) {
			$this->div_class = "";
		}
		if (!is_string($this->div_class)) {
			throw new Exception("div_class must be a string");
		}

		if ($this->remote === false || $this->remote === null ||
			(is_string($this->remote) && trim($this->remote) === "")) {
			$this->remote = false;
		}
		if ($this->remote && !is_string($this->remote)) {
			throw new Exception("remote must be a string which is the URL the form refreshes from");
		}

		foreach ($this->tables as $table) {
			if ($table->get_settings() && $table->get_settings()->uses_pagination() && !$this->remote) {
				// TODO: use DataTableSettings to apply local sorting and filtering defaults
				throw new Exception("Remote URL must be set for pagination settings to be applied.");
			}
		}

		if (!$this->validator_rules) {
			$this->validator_rules = array();
		}
		foreach ($this->validator_rules as $rule) {
			if (!$rule || !($rule instanceof IValidatorRule)) {
				throw new Exception("Validator rules must be of type IValidatorRule");
			}
		}

		return new DataForm($this);
	}

	public function get_tables()
	{
		return $this->tables;
	}

	public function get_forwarded_state() {
		return $this->forwarded_state;
	}

	public function get_form_name() {
		return $this->form_name;
	}

	public function get_method() {
		return $this->method;
	}

	public function get_div_class() {
		return $this->div_class;
	}


	/**
	 * @return bool|string If this is a string then sorting, searching, and pagination options are sent to this URL
	 * If false sorting and searching are done locally
	 */
	public function get_remote() {
		return $this->remote;
	}

	/**
	 * @return IValidatorRule[]
	 */
	public function get_validator_rules() {
		return $this->validator_rules;
	}
}