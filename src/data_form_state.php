<?php
/**
 * This class stores data which was provided about the form through $_REQUEST (ie, what column the user clicked to sort)
 */
class DataFormState
{
	const sorting_state_key = "_sorting_state";
	const sorting_state_asc = "asc";
	const sorting_state_desc = "desc";

	const searching_state_key = "_searching_state";
	const only_display_form = "_only_display_form";

	const forwarded_state_key = "_forwarded_state";

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
	 * @param $request array
	 * @param $current_state DataFormState If not in $request, look in $current_state's forwarded_state
	 * @throws Exception
	 */
	public function __construct($form_name, $request, $current_state=null)
	{
		$this->form_name = $form_name;
		if (!$form_name) {
			throw new Exception("form_name must not be blank");
		}

		if (array_key_exists($form_name, $request)) {
			$form_data = $request[$form_name];
		}
		else {
			if ($current_state) {
				$form_data = $current_state->find_item(array(self::forwarded_state_key, $form_name));
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

		if ($form_data) {
			if (array_key_exists(self::sorting_state_key, $form_data)) {
				if (!is_array($form_data[self::sorting_state_key])) {
					throw new Exception("Sorting state is expected to be an array");
				}
			}

			if (array_key_exists(self::searching_state_key, $form_data)) {
				if (!is_array($form_data[self::searching_state_key])) {
					throw new Exception("Searching state is expected to be an array");
				}
			}
		}
	}

	/**
	 * Searches hash for item that matches path. If $path = array("a", "b"), then this returns
	 * whatever's at {'a' : {'b' : ???}}, or null
	 *
	 * @param $path string[] an array of keys to drill down with
	 * @return object null if nothing found, else whatever value was found
	 * @throws Exception
	 */
	public function find_item($path) {
		if (!is_array($path)) {
			throw new Exception("path must be an array of string keys");
		}
		$current = $this->form_data;
		foreach ($path as $key) {
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
	 * Returns the string to filter on for a given column, or null
	 *
	 * @param $column_key
	 * @return string
	 */
	public function get_searching_state($column_key) {
		return $this->find_item(array(self::searching_state_key));
	}

	/**
	 * Returns 'asc' or 'desc' for a given column, or null
	 *
	 * @param $column_key
	 * @return string
	 */
	public function get_sorting_state($column_key) {
		return $this->find_item(array(self::sorting_state_key));
	}

	/**
	 * @return bool Did client indicate that it wants the raw HTML form?
	 */
	public function only_display_form() {
		return $this->find_item(array(self::only_display_form));
	}

	public function get_form_data() {
		return $this->form_data;
	}

	public function get_form_name()
	{
		return $this->form_name;
	}
}