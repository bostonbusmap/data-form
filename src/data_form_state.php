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
	 * @var string[]
	 */
	private $searching_state;
	/**
	 * @var string[]
	 */
	private $sorting_state;
	/**
	 * @var bool
	 */
	private $only_display_form;

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
	 * @throws Exception
	 */
	public function __construct($form_name, $request)
	{
		$this->form_name = $form_name;
		if (!$form_name) {
			throw new Exception("form_name must not be blank");
		}
		$this->searching_state = array();
		$this->sorting_state = array();

		if (array_key_exists($form_name, $request)) {
			$form_data = $request[$form_name];
		}
		elseif (array_key_exists(DataFormState::forwarded_state_key, $request) &&
			array_key_exists($form_name, $request[DataFormState::forwarded_state_key]))
		{
			$form_data = $request[DataFormState::forwarded_state_key][$form_name];
		}
		else
		{
			$form_data = array();
		}
		$this->form_data = $form_data;

		if ($form_data) {
			if (array_key_exists(self::sorting_state_key, $form_data)) {
				if (!is_array($form_data[self::sorting_state_key])) {
					throw new Exception("Sorting state is expected to be an array");
				}
				$this->sorting_state = $form_data[self::sorting_state_key];
			}

			if (array_key_exists(self::searching_state_key, $form_data)) {
				if (!is_array($form_data[self::searching_state_key])) {
					throw new Exception("Searching state is expected to be an array");
				}
				$this->searching_state = $form_data[self::searching_state_key];
			}

			if (array_key_exists(self::only_display_form, $form_data) && $form_data[self::only_display_form]) {
				$this->only_display_form = true;
			}
		}
	}

	/**
	 * Returns the string to filter on for a given column, or null
	 *
	 * @param $column_key
	 * @return string
	 */
	public function get_searching_state($column_key) {
		if (array_key_exists($column_key, $this->searching_state)) {
			return $this->searching_state[$column_key];
		}
		return null;
	}

	/**
	 * Returns 'asc' or 'desc' for a given column, or null
	 *
	 * @param $column_key
	 * @return string
	 */
	public function get_sorting_state($column_key) {
		if (array_key_exists($column_key, $this->sorting_state)) {
			return $this->sorting_state[$column_key];
		}
		return null;
	}

	/**
	 * @return bool Did client indicate that it wants the raw HTML form?
	 */
	public function only_display_form() {
		return $this->only_display_form;
	}

	public function get_form_data() {
		return $this->form_data;
	}

	public function get_form_name()
	{
		return $this->form_name;
	}
}