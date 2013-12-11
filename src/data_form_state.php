<?php

require_once "data_table_search_state.php";
/**
 * This class stores data which was provided about the form through $_POST or $_GET.
 *
 * Since all fields are formatted similar to form_name[field_name], this is put into
 * $_POST as $_POST[$form_name][$field_name]. DataFormState basically holds the array $_POST[$form_name]
 * in $this->form_data with some exceptions:
 *
 *   - If source_state is specified in the constructor, it will look in the
 *     _forwarded_state field of another DataForm to see if is listed and use that
 *     (eg, $form_data[another_form_name][_state][_forwarded_state][form_name])
 *   - Items from $form_data[form_name][_state][_blanks] are copied to $form_data[form_name]
 *     because certain items like checkboxes don't show up at all if unchecked.
 *   - Then items from $form_data[form_name][_state][_hidden_state] are copied into $form_data[form_name]
 *     as long as it won't overwrite anything.
 *
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
	const hidden_state_key = "_hidden_state";
	const blanks_key = "_blanks";

	const pagination_key = "_pagination";

	const form_exists = "_form_exists";

	const only_validate_key = "_validate";

	/**
	 * The piece of $_POST or $_GET that's relevant to this form, slightly modified
	 * @var array
	 */
	private $form_data;

	/**
	 * @var string Name of form
	 */
	private $form_name;

	/**
	 * Creates a DataFormState
	 *
	 * @param $form_name string
	 * @param $post array should be $_POST or $_GET
	 * @param $source_state DataFormState If not in $post, look in $current_state's forwarded_state and use that
	 * @throws Exception
	 */
	public function __construct($form_name, $post, $source_state=null)
	{
		$this->form_name = $form_name;
		if (!is_string($form_name) || trim($form_name) === "") {
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
				$form_data = $source_state->find_item(array(self::state_key, self::forwarded_state_key, $form_name));
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

		if (is_null($this->form_data)) {
			$this->form_data = array();
		}
		if (!is_array($this->form_data)) {
			throw new Exception("form_data was expected to be an array");
		}

		if ($this->form_data) {
			// just some validation
			foreach (array(self::sorting_state_key, self::searching_state_key, self::pagination_key) as $key) {
				if (isset($this->form_data[self::state_key][$key])) {
					if (!is_array($this->form_data[self::state_key][$key])) {
						throw new Exception("$key is expected to be an array");
					}
				}
			}

			// Copy over blank items in case they aren't sent in $_POST if blank.
			// For items like checkboxes we also have a hidden field set
			// so we can know for sure that an item is blank.
			if (isset($this->form_data[self::state_key][self::blanks_key])) {
				$this->form_data = self::copy_over_array($this->form_name,
					$this->form_data[self::state_key][self::blanks_key], $this->form_data);
			}

			if (isset($this->form_data[self::state_key][self::hidden_state_key])) {
				$this->form_data = self::copy_over_array($this->form_name,
					$this->form_data[self::state_key][self::hidden_state_key], $this->form_data);
			}
		}
	}

	/**
	 * Copy every src[k] to dest[k] and return the newly merged array
	 *
	 * Like array_merge but numeric indexes are not treated any differently, and dest is not overwritten
	 *
	 * @param $base string
	 * @param $src array
	 * @param $dest array
	 * @return array
	 */
	private static function copy_over_array($base, $src, $dest) {
		if (!is_array($dest) || !is_array($src)) {
			return $src;
		}
		foreach ($src as $k => $v) {
			if (isset($dest[$k])) {
				if (is_array($dest[$k])) {
					$dest[$k] = self::copy_over_array($base . "[" . $k . "]", $v, $dest[$k]);
				}
				else
				{
					// don't overwrite
				}
			}
			else
			{
				$dest[$k] = $v;
			}
		}
		return $dest;
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
			if (trim($key) === "") {
				throw new Exception("Each item in path must exist");
			}
			if (!is_array($current)) {
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
	 * Checks if the lookup path has an item
	 *
	 * This is different than just find_item($path) === null because that will not distinguish between
	 * null and missing values
	 *
	 * @param $path string[] Lookup path for array
	 * @return bool
	 * @throws Exception
	 */
	public function has_item($path) {
		if (!is_array($path)) {
			throw new Exception("path must be an array of string keys");
		}
		if (count($path) === 0) {
			return false;
		}

		if (count($path) === 1) {
			return isset($this->form_data[$path[0]]);
		}
		else {
			$subset = array_slice($path, 0, count($path) - 1);
			$remainder = $this->find_item($subset);
			if (is_array($remainder)) {
				return array_key_exists($path[count($path) - 1], $remainder);
			}
			else
			{
				return false;
			}
		}
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
		if (!is_string($form_name) || trim($form_name) === "") {
			throw new Exception("form_name must be a non-empty string");
		}
		if (!is_array($path)) {
			throw new Exception("path must be an array of strings");
		}
		if (!$path) {
			throw new Exception("path must have at least one string");
		}
		$ret = $form_name;
		foreach ($path as $item) {
			if (!is_string($item)) {
				throw new Exception("Each item in path must be a string");
			}
			if (trim($item) === "") {
				throw new Exception("Each item in path must not be empty");
			}
			if (strpos($item, "[") !== false || strpos($item, "]") !== false) {
				throw new Exception("Cannot use square bracket within item name");
			}
			$ret .= "[" . $item . "]";
		}
		return $ret;
	}

	/**
	 * Returns a DataTableSearchType
	 *
	 * @param $column_key string
	 * @param $table_name string Optional table name. If falsey, use state for whole form
	 * @return DataTableSearchState. May be null if params don't exist
	 * @throws Exception
	 */
	public function get_searching_state($column_key, $table_name="") {
		$search_key = self::get_searching_state_key($column_key, $table_name);
		$params_key = array_merge($search_key, array(DataTableSearchState::params_key));
		$type_key = array_merge($search_key, array(DataTableSearchState::type_key));

		$type = $this->find_item($type_key);
		$params = $this->find_item($params_key);

		if (is_null($type) && is_null($params)) {
			return null;
		}
		else
		{
			// if only one is null, this should be validated in constructor
			return new DataTableSearchState($type, $params);
		}
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
	 * @return bool Is client only looking to validate the form, not submit it?
	 */
	public function only_validate() {
		return $this->find_item(self::only_validate_key());
	}

	/**
	 * @return string[]
	 */
	public static function only_validate_key() {
		return array(self::state_key, self::only_validate_key);
	}

	/**
	 * @return string[]
	 */
	public static function get_hidden_state_key() {
		return array(self::state_key, self::hidden_state_key);
	}

	/**
	 * @return string[]
	 */
	public static function get_blanks_key() {
		return array(self::state_key, self::blanks_key);
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