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
 * Hidden input field
 */
class DataTableHidden implements IDataTableWidget {
	/** @var string Value of element */
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
	 * Renders hidden field
	 *
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
	 * @param $form_name string Name of form
	 * @param $state DataFormState State of form
	 * @param $name_array string[] Array of names which will become the field name, like form_name[a1][a2][a3]...
	 * @param $default_value string Value if no value in state
	 * @return string HTML
	 */
	public static function display_hidden($form_name, $state, $name_array, $default_value) {
		$qualified_name = DataFormState::make_field_name($form_name, $name_array);

		// NOTE: if something uses Javascript to update a hidden field, we would need
		// to rethink this, probably by adding some way to let user force using the default_value
		// instead of what's in $state
		$value = $default_value;

		$ret = '<input type="hidden" id="' . htmlspecialchars($qualified_name) . '" name="' . htmlspecialchars($qualified_name) . '" value="' . htmlspecialchars($value) . '" />';
		return $ret;
	}

	public function get_placement()
	{
		// doesn't matter for a hidden input
		return IDataTableWidget::placement_top;
	}
}