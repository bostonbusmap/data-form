<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

require_once "data_form_builder.php";
require_once "data_table.php";

/**
 * Renders an HTML form with pagination, sorting, filtering and validation. See examples directory for common
 * usages.
 *
 * The object is created using a DataFormBuilder object to specify parameters. Then the user can call $form->display($state)
 * to render the form HTML, with $state being a DataFormState object that represents what was submitted to the form via POST or GET.
 * To properly create an AJAX-friendly form, see the examples (the sql.php example for instance).
 *
 * All fields inside the form must have this syntax: form_name[field_name][rowid]. See DataFormState for more information on
 * field names.
 *
 * The form looks like this:
 *
 * <div id='form_name'>
 * <div id='form_name_flash'><!-- error messages go here --></div>
 * <form action='' name='form_name'> <!-- action is set by buttons using JS right before submit -->
 * <input type='hidden' value='true' name='form_name[_state][_form_exists]' />
 * <!-- hidden fields for forwarded state -->
 * <!-- hidden fields for hidden rows -->
 * <a onclick='widgetBehavior();'>Link at top of table</a>
 * <table>
 *  <caption><!-- pagination stuff --></caption>
 *  <thead>
 *   <!-- row for header names -->
 *   <!-- optional row with filtering controls -->
 *  </thead>
 *  <tbody>
 * 	 <!-- lots of rows and cells with data -->
 *  </tbody>
 * </table>
 * </form>
 *
 * </div>
 *
 */
class DataForm {
	/** @var  DataTable[] A list of tables which will render HTML tables */
	protected $tables;

	/** @var string The name of the form */
	private $form_name;


	/**
	 * @var  DataFormState[]
	 *
	 * If user is creating forms on multiple pages, they can forward the state from the previous page by adding all of the
	 * DataFormStates to an array and setting this field
	 */
	private $forwarded_state;

	/** @var  string Form method, either GET or POST */
	private $method;

	/** @var  string CSS class for the DIV which wraps everything */
	private $div_class;
	/** @var string|bool
	 * A URL to send pagination, sorting, or searching requests to.
	 * If this is false, the form is assumed to be local and sorting and filtering
	 * are done in Javascript instead.
	 *
	 * If using $_SERVER['REQUEST_URI'] make sure to remove the query string first
	 * */
	private $remote;

	/**
	 * @var IValidatorRule[]|callable[] A list of validation rules to apply. See validate() for use
	 */
	private $validator_rules;


	/**
	 * Use DataFormBuilder::build()
	 *
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
		$this->validator_rules = $builder->get_validator_rules();
	}

	/**
	 * Returns HTML for a div wrapping a form
	 * @param DataFormState $state
	 * @throws Exception
	 * @return string HTML
	 */
	public function display($state=null) {
		$writer = new StringWriter();
		$this->display_using_writer($writer, $state);
		return $writer->get_contents();
	}

	/**
	 * Returns HTML for a div wrapping a form
	 * @param IWriter $writer Where to output HTML
	 * @param DataFormState $state
	 * @throws Exception
	 * @return void
	 */
	public function display_using_writer($writer, $state=null) {
		if ($state && !($state instanceof DataFormState)) {
			throw new Exception("state must be instance of DataFormState");
		}
		if (!$state && $this->remote) {
			throw new Exception("If form is a remote form, state must be specified");
		}

		$writer->write('<div class="' . htmlspecialchars($this->div_class) . '" id="' . htmlspecialchars($this->form_name) . '">');
		$this->display_form_html_using_writer($writer, $state);
		$writer->write('</div>');
	}


	/**
	 * Returns JSON for just the form element
	 * @param DataFormState $state The state which holds what the user is working on
	 * @return string JSON
	 * @throws Exception
	 */
	public function display_form($state=null) {
		$writer = new StringWriter();
		$this->display_form_using_writer($writer, $state);
		return $writer->get_contents();
	}

	/**
	 * Writes JSON for just the form element
	 * @param IWriter $writer Where to output HTML to
	 * @param DataFormState $state The state which holds what the user is working on
	 * @return void
	 * @throws Exception
	 */
	public function display_form_using_writer($writer, $state=null) {
		if ($state && !($state instanceof DataFormState)) {
			throw new Exception("state must be instance of DataFormState");
		}
		if (!$state && $this->remote) {
			throw new Exception("If form is a remote form, state must be specified");
		}

		$writer->write('{"html" : "');
		$error = null;
		try
		{
			$json_writer = new JsonStringWriter($writer);
			$this->display_form_html_using_writer($json_writer, $state);
		}
		catch (Exception $e) {
			$error = $e->getMessage();
		}
		$writer->write('", "status" : ');


		if ($error !== null) {
			$writer->write(json_encode("error"));
			$writer->write(', "error" : ');
			$writer->write(json_encode($error));
			$writer->write("}\n");
		}
		else
		{
			$writer->write(json_encode("success"));
			$writer->write("}\n");
		}
	}

	/**
	 * Returns HTML for just the form element
	 * @param IWriter $writer Where to output HTML to
	 * @param DataFormState $state The state which holds what the user is working on
	 * @return void
	 * @throws Exception
	 */
	protected function display_form_html_using_writer($writer, $state=null) {
		if ($state && !($state instanceof DataFormState)) {
			throw new Exception("state must be instance of DataFormState");
		}
		if (!$state && $this->remote) {
			throw new Exception("If form is a remote form, state must be specified");
		}

		// flash space for messages
		$writer->write('<div class="data-form-flash" id="' . htmlspecialchars($this->form_name . "_flash") . '"></div>');

		// form action is set in javascript
		$writer->write('<form accept-charset="utf-8" name="' . htmlspecialchars($this->form_name) . '" method="' . htmlspecialchars($this->method) . '">');

		$exists_field_name = DataFormState::make_field_name($this->form_name, DataFormState::exists_key());
		$writer->write('<input type="hidden" name="' . htmlspecialchars($exists_field_name) . '" value="true" />');

		foreach ($this->forwarded_state as $forwarded_state) {
			$writer->write(self::make_hidden_inputs_from_array($forwarded_state->get_form_data(),
				DataFormState::make_field_name($this->form_name,
					array(DataFormState::state_key, DataFormState::forwarded_state_key, $forwarded_state->get_form_name()))));
		}

		if ($state && $state->exists()) {
			// We've already integrated old hidden data into state where it doesn't overwrite (in DataFormState's constructor)
			// Now write these values as items for new hidden state
			$new_hidden_data = $state->get_form_data();
			unset($new_hidden_data[DataFormState::state_key]);

			$writer->write(self::make_hidden_inputs_from_array($new_hidden_data,
				DataFormState::make_field_name($this->form_name, array(DataFormState::state_key, DataFormState::hidden_state_key))));
		}

		foreach ($this->tables as $table) {
			$table->display_table_using_writer($this->form_name, $this->method, $this->remote, $writer, $state);
		}
		$writer->write("</form>");
	}

	/**
	 * Writes a bunch of hidden inputs for $obj. Names are concatenated such that a[b][c] will be stored
	 * in $_POST or $_GET as assoc arrays like {'a' : {'b' : {'c' : value}}}
	 *
	 * @param $obj array|string|number either an array with more hidden inputs, or something convertable to a string
	 * @param $base string Prefix for input name
	 * @throws Exception
	 * @return string HTML of hidden inputs
	 */
	private static function make_hidden_inputs_from_array($obj, $base) {
		$array = self::make_field_names($obj, $base);
		$ret = "";
		foreach ($array as $k => $v) {
			$ret .= '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars($v) . '" />';
		}
		return $ret;
	}

	/**
	 * Returns list of field names as keys like "a[b][c]" => "value"
	 *
	 * @param $obj array|string|number either an array with more hidden inputs, or something convertable to a string
	 * @param $base string Prefix for input name
	 * @throws Exception
	 * @return string[]
	 */
	private static function make_field_names($obj, $base)
	{
		if (!is_string($base)) {
			throw new Exception("base must be a string");
		}
		if (trim($base) === "") {
			throw new Exception("base must not be empty");
		}

		$ret = array();
		if (is_array($obj)) {
			foreach ($obj as $k => $v) {
				if (!is_string($k) && !is_int($k)) {
					throw new Exception("keys in obj must be strings or integers");
				}
				if (trim($k) === "") {
					throw new Exception("keys in obj must not be empty");
				}
				if (strpos($k, "[") !== false || strpos($k, "]") !== false) {
					throw new Exception("square brackets not permitted in keys");
				}

				$results = self::make_field_names($obj[$k], $base . "[" . $k . "]");
				foreach ($results as $sub_key => $sub_value) {
					$ret[$sub_key] = $sub_value;
				}
			}
		}
		else
		{
			$ret[$base] = $obj;
		}
		return $ret;
	}

	/**
	 * Runs validation rules and returns a JSON string with validation errors if any were found
	 *
	 * @param $state DataFormState What the user was working on
	 * @return string JSON with validation errors as HTML. Must be an empty string if no errors found
	 * @throws Exception
	 */
	public function validate($state)
	{
		if (!$state || !($state instanceof DataFormState)) {
			throw new Exception("state must exist and be of type DataFormState");
		}
		$errors = array();
		foreach ($this->validator_rules as $rule) {
			if ($rule instanceof IValidatorRule) {
				$error = $rule->validate($state);
			}
			else
			{
				$error = $rule($state);
			}
			if ($error && !is_string($error)) {
				throw new Exception("error must be a string");
			}
			if ($error) {
				$errors[] = '<span class="error">' . htmlspecialchars($error) . "</span>";
			}
		}
		if ($errors) {
			$errors_html = join("<br />", $errors);
			return json_encode(array(
				"status" => "error",
				"error" => $errors_html
			));
		}
		else
		{
			return json_encode(array(
				"status" => "success"
			));
		}
	}

}