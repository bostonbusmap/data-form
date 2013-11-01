<?php
/**
 * This class stores data which was provided about the form through $_POST (ie, what column the user clicked to sort)
 */
class DataFormState
{
	const state_key = "_state";

	const sorting_state_key = "_sorting_state";
	const sorting_state_asc = "asc";
	const sorting_state_desc = "desc";

	const searching_state_key = "_searching_state";
	const only_display_form = "_only_display_form";

	const forwarded_state_key = "_forwarded_state";

	const pagination_key = "_pagination";

	const form_exists = "_form_exists";

	/**
	 * The piece of $_REQUEST that's relevant to this form
	 * @var array
	 */
	private $form_data;

	/**
	 * @var string
	 */
	private $form_name;

	/**
	 * @param $form_name string
	 * @param $post array should be $_POST or $_GET
	 * @param $source_state DataFormState If not in $request, look in $current_state's forwarded_state
	 * @throws Exception
	 */
	public function __construct($form_name, $post, $source_state=null)
	{
		$this->form_name = $form_name;
		if (!$form_name || !is_string($form_name)) {
			throw new Exception("form_name must not be blank and must be a string");
		}

		if (!is_array($post)) {
			throw new Exception("post must be an array, probably POST or GET global variables");
		}
		if (array_key_exists($form_name, $post)) {
			$form_data = $post[$form_name];
		}
		else {
			if ($source_state) {
				$form_data = $source_state->find_item(array(self::forwarded_state_key, $form_name));
				if (!$form_data) {
					$form_data = array();
				}
			}
			else
			{
				$form_data = array();
			}
		}
		$this->form_data = $form_data;

		if (!$this->form_data) {
			$this->form_data = array();
		}

		if ($this->form_data) {
			foreach (array(self::sorting_state_key, self::searching_state_key, self::pagination_key) as $key) {
				if (array_key_exists($key, $this->form_data)) {
					if (!is_array($this->form_data[$key])) {
						throw new Exception("$key is expected to be an array");
					}
				}
			}
		}
	}

	/**
	 * Searches hash for item that matches path. If $path = array("a", "b"), then this returns
	 * whatever's at {'a' : {'b' : ???}}, or null
	 *
	 * @param $path string[] an array of keys to drill down with
	 * @return array|string|number null if nothing found, else whatever value was found
	 * @throws Exception
	 */
	public function find_item($path) {
		if (!is_array($path)) {
			throw new Exception("path must be an array of string keys");
		}
		$current = $this->form_data;
		foreach ($path as $key) {
			if (!is_string($key)) {
				throw new Exception("Each item in path must be a string");
			}
			if (!$current) {
				return null;
			}
			elseif (array_key_exists($key, $current)) {
				$current = $current[$key];
			}
			else
			{
				return null;
			}
		}
		return $current;
	}

	/**
	 * Concatenate $form_name and items in $path to make an HTML field name
	 *
	 * @param $form_name string
	 * @param $path string[]
	 * @return string
	 * @throws Exception
	 */
	public static function make_field_name($form_name, $path) {
		if (!$form_name || !is_string($form_name)) {
			throw new Exception("form_name must be a non-empty string");
		}
		if (!is_array($path)) {
			throw new Exception("path must be an array of strings");
		}
		$ret = $form_name;
		foreach ($path as $item) {
			if (!is_string($item)) {
				throw new Exception("Each item in path must be a string");
			}
			if (strpos($item, "[") !== false || strpos($item, "]") !== false) {
				throw new Exception("Cannot use square bracket within item name");
			}
			$ret .= "[" . $item . "]";
		}
		return $ret;
	}

	/**
	 * Returns the string to filter on for a given column, or null
	 *
	 * @param $column_key string
	 * @param $table_name string Optional table name. If falsey, use state for whole form
	 * @return string
	 */
	public function get_searching_state($column_key, $table_name="") {
		return $this->find_item(self::get_searching_state_key($column_key, $table_name));
	}

	/**
	 * @param $column_key string
	 * @param $table_name string
	 * @return string[]
	 */
	public static function get_searching_state_key($column_key, $table_name) {
		if (!$table_name) {
			return array(self::state_key, self::searching_state_key, $column_key);
		}
		else
		{
			return array(self::state_key, $table_name, self::searching_state_key, $column_key);
		}
	}

	/**
	 * Returns 'asc' or 'desc' for a given column, or null
	 *
	 * @param $column_key string
	 * @param $table_name string Optional table name. If falsey, use state for whole form
	 * @return string
	 */
	public function get_sorting_state($column_key, $table_name="") {
		return $this->find_item(self::get_sorting_state_key($column_key, $table_name));
	}

	/**
	 * @param $column_key string
	 * @param $table_name string
	 * @return string[]
	 */
	public static function get_sorting_state_key($column_key, $table_name) {
		if (!$table_name) {
			return array(self::state_key, self::sorting_state_key, $column_key);
		}
		else
		{
			return array(self::state_key, $table_name, self::sorting_state_key, $column_key);
		}
	}

	/**
	 * @param string $table_name Optional table name. If set, gets the pagination state for the table, else gets
	 * the pagination state for the whole form
	 * @throws Exception
	 * @return DataTablePaginationState The form data relevant to data table pagination
	 */
	public function get_pagination_state($table_name="") {
		return new DataTablePaginationState($this->find_item(self::get_pagination_state_key($table_name)));
	}

	/**
	 * @param $table_name string
	 * @return string[]
	 */
	public static function get_pagination_state_key($table_name) {
		if (!$table_name) {
			return array(self::state_key, self::pagination_key);
		}
		else
		{
			return array(self::state_key, $table_name, self::pagination_key);
		}
	}

	/**
	 * @return bool Did client indicate that it wants the raw HTML form?
	 *
	 * This is true if user wants to do an AJAX refresh of the form
	 */
	public function only_display_form() {
		return $this->find_item(self::only_display_form_key());
	}

	/**
	 * @return string[]
	 */
	public static function only_display_form_key() {
		return array(self::state_key, self::only_display_form);
	}

	/**
	 * @return bool
	 */
	public function exists() {
		return $this->find_item(self::exists_key());
	}

	public static function exists_key() {
		return array(self::state_key, self::form_exists);
	}

	/**
	 * @return array The data from $_POST specific to this state
	 */
	public function get_form_data() {
		return $this->form_data;
	}

	/**
	 * @return string
	 */
	public function get_form_name()
	{
		return $this->form_name;
	}
}