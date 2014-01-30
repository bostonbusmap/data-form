<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

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
 *
 *   - Then items from $form_data[form_name][_state][_hidden_state] are copied into $form_data[form_name]
 *     as long as it won't overwrite anything in $form_data[form_name] or $form_data[form_name][_state][_blanks]
 *
 */
class DataFormState
{
	/**
	 * All special hidden fields should go under form_name[state_key]
	 */
	const state_key = "_s";

	/**
	 * Holds ordering information for columns
	 */
	const sorting_state_key = "_srt";
	const sorting_state_asc = "asc";
	const sorting_state_desc = "desc";

	/**
	 * Holds filtering information for columns
	 */
	const searching_state_key = "_sch";
	/**
	 * If form_name[state_key][only_display_form] is set, page should render only the form HTML, not the whole page
	 */
	const only_display_form = "_d";

	/**
	 * form_name[state_key][forwarded_state_key] contains form state from previous pages
	 */
	const forwarded_state_key = "_f";
	/**
	 * form_name[state_key][hidden_state_key] contains form state for hidden rows or other items
	 */
	const hidden_state_key = "_h";
	/**
	 * form_name[state_key][blanks_key] contains fields which exist elsewhere in the form
	 * but whose information may not be sent if field is unselected. For example checkboxes
	 * would define a blank item under this key which would always be sent even if checkbox is
	 * unchecked.
	 */
	const blanks_key = "_b";

	const pagination_key = "_p";

	const form_exists = "_e";

	const only_validate_key = "_v";

	const reset_key = "_r";

	/**
	 * The piece of $_POST or $_GET that's relevant to this form, slightly modified
	 * @var array
	 */
	private $form_data;
	/**
	 * The same as $this->form_data but with blank values included
	 * @var array
	 */
	private $form_data_with_blanks;

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

		if (is_null($form_data)) {
			$form_data = array();
		}
		if (!is_array($form_data)) {
			throw new Exception("form_data was expected to be an array");
		}

		if (array_key_exists(self::state_key, $form_data)) {
			$old_form_state = $form_data[self::state_key];
			if (array_key_exists(self::reset_key, $form_data[self::state_key])) {
				// Reset form
				$form_data = array();

				$special_keys = array(self::form_exists, self::only_display_form,
					self::forwarded_state_key);

				// preserve these keys since they don't refer to the data, just how we are accessing the DataForm
				foreach ($special_keys as $special_key) {
					if (array_key_exists($special_key, $old_form_state)) {
						$form_data[self::state_key][$special_key] = $old_form_state[$special_key];
					}
				}
			}
		}

		// just some validation
		foreach (array(self::sorting_state_key, self::searching_state_key, self::pagination_key) as $key) {
			if (isset($form_data[self::state_key][$key])) {
				if (!is_array($form_data[self::state_key][$key])) {
					throw new Exception("$key is expected to be an array");
				}
			}
		}

		// For items like checkboxes we also have a hidden field set
		// so we can know for sure that an item is blank.
		if (isset($form_data[self::state_key][self::blanks_key])) {
			$blanks = $form_data[self::state_key][self::blanks_key];
		} else {
			$blanks = array();
		}

		// All items are stored as hidden fields in hidden_state_key
		// so we preserve the values during pagination or filtering rows
		if (isset($form_data[self::state_key][self::hidden_state_key])) {
			$history = $form_data[self::state_key][self::hidden_state_key];
		} else {
			$history = array();
		}

		// Blanks are empty values for fields which exist but don't explicitly send their empty
		// values in HTTP requests, like checkboxes. A blank item has a key but a null value, which
		// makes it useful to test for existence of items.

		// Blanks are trying to solve the problem of keeping track of unchecked items
		// even when $history references the item. Unchecked items aren't sent in $_POST at all
		// so if we just copied $history it would overwrite the unchecked items, leaving it checked.

		// First this copies $blanks over $form_data as long as it doesn't overwrite anything.
		// This puts empty strings in the form data to take place of unchecked items.
		$form_data_with_blanks_without_history = self::copy_over_array($blanks, $form_data, $form_data);
		// Then we make a copy of history with all current form data removed.
		$form_data_history_only = self::copy_over_array($history, $form_data_with_blanks_without_history, array());
		// Then we copy history_only over the current data
		$form_data = self::copy_over_array($form_data_history_only, array(), $form_data);
		// Make an array with blanks included so we can test for existance properly
		$this->form_data_with_blanks = self::copy_over_array($form_data_history_only, array(), $form_data_with_blanks_without_history);

		$this->form_data = $form_data;
	}


	/**
	 * Copy everything from $src to $dest, overwriting whatever is in $dest
	 * as long as the value at the same key doesn't exist in $dont_overwrite.
	 *
	 * If $src is not an array we return $src
	 *
	 * Note that we just care if the key exists in $dont_overwrite and $dont_overwrite[$key] is not an array.
	 * None of those values are used in the result.
	 *
	 * Returns the modified $dest
	 *
	 * @param $src array
	 * @param $dont_overwrite array
	 * @param $dest array
	 * @return array
	 */
	private static function copy_over_array($src, $dont_overwrite, $dest) {
		if (!is_array($src)) {
			// We default to overwriting $dest with $src unless $dont_overwrite's key matches $src's key
			// but we don't have any information about keys here. That check should have been done by calling function
			return $src;
		}

		if (!is_array($dest)) {
			// both src and dont_overwrite are arrays at this point so whatever we return must
			// be an array too
			$dest = array();
		}

		foreach ($src as $src_k => $src_v) {
			// If key is not in dont_overwrite just copy everything
			// else call this function recursively to do the same check for nested arrays
			if (isset($dest[$src_k])) {
				$dest_v = $dest[$src_k];
			}
			else
			{
				$dest_v = null;
			}

			if (is_array($dont_overwrite) && array_key_exists($src_k, $dont_overwrite)) {
				$dont_overwrite_v = $dont_overwrite[$src_k];
				if (is_array($dont_overwrite_v)) {
					$dest[$src_k] = self::copy_over_array($src_v, $dont_overwrite_v, $dest_v);
				}
				else
				{
					// value exists in $dont_overwrite, so leave $dest as it is
				}
			}
			else
			{
				$dest[$src_k] = self::copy_over_array($src_v, null, $dest_v);
			}

		}
		return $dest;
	}

	/**
	 * Searches array for item that matches path. If $path = array("a", "b"), then this returns
	 * whatever's at {'a' : {'b' : ???}}, or null
	 *
	 * @param $path string[] an array of keys to drill down with
	 * @param $start_array array Array to search inside. Either this->form_data or this->form_data_with_blanks
	 * @return array|string|number null if nothing found, else whatever value was found
	 * @throws Exception
	 */
	protected static function do_find_item($path, $start_array) {
		if (!is_array($path)) {
			throw new Exception("path must be an array of string keys");
		}
		if (!is_array($start_array)) {
			throw new Exception("start_array must be an array");
		}
		$current = $start_array;
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
	 * Searches array for item that matches path. If $path = array("a", "b"), then this returns
	 * whatever's at {'a' : {'b' : ???}}, or null
	 *
	 * Be careful to use has_item if you only care if an item exists, since it will take into account
	 * blank values
	 *
	 * @param $path string[] an array of keys to drill down with
	 * @return array|string|number null if nothing found, else whatever value was found
	 * @throws Exception
	 */
	public function find_item($path) {
		return self::do_find_item($path, $this->form_data);
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
			return array_key_exists($path[0], $this->form_data_with_blanks);
		}
		else {
			$subset = array_slice($path, 0, count($path) - 1);
			$remainder = self::do_find_item($subset, $this->form_data_with_blanks);
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
	 * @return string field name
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
	 * A string array which can be used with make_field_name to get a field name for search state
	 *
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
	 * @return string 'asc' or 'desc' or null if unspecified for column
	 */
	public function get_sorting_state($column_key, $table_name="") {
		return $this->find_item(self::get_sorting_state_key($column_key, $table_name));
	}

	/**
	 * A string array which can be used with make_field_name to get a field name for sorting state
	 *
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
	 * Returns a DataTablePaginationState for a table, which contains limit and page information
	 *
	 * @param string $table_name Optional table name. If set, gets the pagination state for the table, else gets
	 * the pagination state for the whole form
	 * @throws Exception
	 * @return DataTablePaginationState The form data relevant to data table pagination
	 */
	public function get_pagination_state($table_name="") {
		return new DataTablePaginationState($this->find_item(self::get_pagination_state_key($table_name)));
	}

	/**
	 * A string array which can be used with make_field_name to get a field name for pagination state
	 *
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
	 * Returns true if client indicates that it wants only HTML for form to be rendered, not whole page
	 *
	 * @return bool Did client indicate that it wants the raw HTML form?
	 *
	 * This is true if user wants to do an AJAX refresh of the form
	 */
	public function only_display_form() {
		return $this->find_item(self::only_display_form_key());
	}

	/**
	 * A string array which can be used with make_field_name to get a field name
	 *
	 * @return string[]
	 */
	public static function only_display_form_key() {
		return array(self::state_key, self::only_display_form);
	}

	/**
	 * Returns true if client indicates it wants only validation text to be rendered, not whole page.
	 *
	 * @return bool Is client only looking to validate the form, not submit it?
	 */
	public function only_validate() {
		return $this->find_item(self::only_validate_key());
	}

	/**
	 * A string array which can be used with make_field_name to get a field name
	 *
	 * @return string[]
	 */
	public static function only_validate_key() {
		return array(self::state_key, self::only_validate_key);
	}

	/**
	 * A string array which can be used with make_field_name to get a field name
	 *
	 * @return string[]
	 */
	public static function get_hidden_state_key() {
		return array(self::state_key, self::hidden_state_key);
	}

	/**
	 * A string array which can be used with make_field_name to get a field name
	 *
	 * @return string[]
	 */
	public static function get_blanks_key() {
		return array(self::state_key, self::blanks_key);
	}

	/**
	 * Returns true if this DataFormState found anything in $_POST or $_GET for it.
	 *
	 * @return bool
	 */
	public function exists() {
		return $this->find_item(self::exists_key());
	}

	/**
	 * A string array which can be used with make_field_name to get a field name
	 *
	 * @return string[]
	 */
	public static function exists_key() {
		return array(self::state_key, self::form_exists);
	}

	/**
	 * Should we ignore the state and use defaults, effectively resetting the form?
	 *
	 * @return bool
	 */
	public function get_reset() {
		return $this->has_item(self::get_reset_key());
	}

	/**
	 * @return string[]
	 */
	public static function get_reset_key() {
		return array(self::state_key, self::reset_key);
	}

	/**
	 * The slightly manipulated data from $_POST or $_GET
	 *
	 * @return array The data from $_POST specific to this state
	 */
	public function get_form_data() {
		return $this->form_data;
	}

	/**
	 * The form name
	 *
	 * @return string The form name
	 */
	public function get_form_name()
	{
		return $this->form_name;
	}
}