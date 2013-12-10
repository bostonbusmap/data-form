<?php
/**
 * A hidden input
 */
class DataTableHidden implements IDataTableWidget {
	/** @var string */
	protected $value;
	/** @var  string Name of field element (will become form_name[name]) */
	protected $name;

	/**
	 * @param $builder DataTableHiddenBuilder
	 * @throws Exception
	 */
	public function __construct($builder) {
		if (!($builder instanceof DataTableHiddenBuilder)) {
			throw new Exception("builder expected to be instance of DataTableHiddenBuilder");
		}
		$this->value = $builder->get_value();
		$this->name = $builder->get_name();
	}

	/**
	 * @param $form_name string Name of form
	 * @param $form_method string GET or POST
	 * @param $state DataFormState
	 * @return string HTML
	 */
	public function display($form_name, $form_method, $state)
	{
		return self::display_hidden($form_name, $state, array($this->name), $this->value);
	}

	/**
	 * Returns hidden input field with either whatever's in $state or otherwise $default_value
	 *
	 * @param $form_name string
	 * @param $state DataFormState
	 * @param $name_array string[]
	 * @param $default_value string
	 * @return string HTML
	 */
	public static function display_hidden($form_name, $state, $name_array, $default_value) {
		$qualified_name = DataFormState::make_field_name($form_name, $name_array);

		if ($state->has_item($name_array)) {
			$value = $state->find_item($name_array);
		}
		else
		{
			$value = $default_value;
		}

		$ret = '<input type="hidden" id="' . htmlspecialchars($qualified_name) . '" name="' . htmlspecialchars($qualified_name) . '" value="' . htmlspecialchars($value) . '" />';
		return $ret;
	}

	public function get_placement()
	{
		// doesn't matter for a hidden input
		return IDataTableWidget::placement_top;
	}
}