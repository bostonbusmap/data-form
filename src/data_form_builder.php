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
 * Builder for DataForm
 */
class DataFormBuilder {
	/** @var  DataTable[] A list of tables which will render HTML tables */
	protected $tables;

	/** @var string The name of the form */
	private $form_name;


	/** @var  DataFormState[]
	 * If user is creating forms on multiple pages, they can forward the state from the previous page by adding all of the
	 * DataFormStates to an array and setting this field
	 */
	private $forwarded_state;

	/** @var  string Form submit method, either POST or GET. POST by default */
	private $method;

	/** @var  string CSS class for div */
	private $div_class;

	/** @var string|bool
	 * A URL to send pagination, sorting, or searching requests to.
	 * If this is false, the form is assumed to be local and sorting and filtering
	 * are done in Javascript instead.
	 *
	 * If using $_SERVER['REQUEST_URI'] for this, make sure to remove the query string first
	 * */
	private $remote;

	/** @var IValidatorRule[]|callable[] A list of rules to apply for validation */
	private $validator_rules;

	/**
	 * @param $form_name string Name of form
	 */
	public function __construct($form_name) {
		$this->form_name = $form_name;
	}

	/**
	 * Allows user to chain methods after this one. As of PHP 5.2 the new syntax does not allow this
	 * but otherwise these are equivalent
	 *
	 * @param $form_name string Name of form
	 * @return DataFormBuilder
	 */
	public static function create($form_name) {
		return new DataFormBuilder($form_name);
	}

	/**
	 * A list of tables which will render HTML tables
	 *
	 * @param $tables DataTable[] A list of tables which will render HTML tables
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
	 * HTTP form submit method
	 *
	 * @param $method string Either POST or GET
	 * @return DataFormBuilder
	 */
	public function method($method) {
		$this->method = $method;
		return $this;
	}

	/**
	 * The CSS class for the DIV surrounding the form
	 *
	 * @param $div_class string CSS class
	 * @return DataFormBuilder
	 */
	public function div_class($div_class) {
		$this->div_class = $div_class;
		return $this;
	}

	/**
	 * If this is a string then sorting, searching, and pagination options are sent to this URL.
	 * Be careful about using $_SERVER['REQUEST_URI'] for this since the query string is included in this, which
	 * you probably don't want.
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

	/**
	 * List of rules for validation
	 *
	 * @param $validator_rules callable[]|IValidatorRule[]
	 * @return DataFormBuilder
	 */
	public function validator_rules($validator_rules) {
		$this->validator_rules = $validator_rules;
		return $this;
	}


	/**
	 * Validates input and creates a DataForm
	 *
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
			$this->method = "GET";
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
			$this->div_class = "data-form";
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
			if (!($rule instanceof IValidatorRule) && !(is_callable($rule))) {
				throw new Exception("Validator rules must be of type IValidatorRule or a callback taking state as its argument");
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
	 * @return IValidatorRule[]|callable[]
	 */
	public function get_validator_rules() {
		return $this->validator_rules;
	}
}