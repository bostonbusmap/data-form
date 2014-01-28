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
 * Renders radio options with value of cell data
 */
class DataTableRadioFormatter implements IDataTableCellFormatter {

	/**
	 * Writes out radio buttons
	 *
	 * @param string $form_name Name of HTML form
	 * @param string $column_header Column key
	 * @param object $column_data Unused
	 * @param string $rowid ID for the cell's row
	 * @param DataFormState $state
	 * @return string HTML formatted column data
	 */
	public function format($form_name, $column_header, $column_data, $rowid, $state)
	{
		if ($state) {
			$name_array = array($column_header);
		}
		else
		{
			$name_array = array();
		}
		return DataTableRadio::format_radio($form_name, "POST", $name_array, "", $column_data, false, $state, "");
	}
}

/**
 * Radio button
 */
class DataTableRadio implements IDataTableWidget {
	/**
	 * @var string Field name
	 */
	protected $name;
	/**
	 * @var string Checkbox value
	 */
	protected $value;
	/**
	 * @var bool Checked by default?
	 */
	protected $checked_by_default;
	/**
	 * @var string Either 'top' or 'bottom'
	 */
	protected $placement;
	/**
	 * @var string HTML label
	 */
	protected $label;
	/**
	 * @var string ID name
	 */
	protected $id;

	/**
	 * @param $builder DataTableRadioBuilder
	 * @throws Exception
	 */
	public function __construct($builder) {
		if (!($builder instanceof DataTableRadioBuilder)) {
			throw new Exception("builder must be of type DataTableRadioBuilder");
		}

		$this->name = $builder->get_name();
		$this->value = $builder->get_value();
		$this->checked_by_default = $builder->get_checked_by_default();
		$this->placement = $builder->get_placement();
		$this->label = $builder->get_label();
		$this->id = $builder->get_id();
	}

	/**
	 * Write out radio buttons
	 *
	 * Unlike other elements this has a different field name than the id name. Radio buttons must have the same field name
	 * to match with each other but also must have different IDs because that's how IDs work
	 *
	 * @param $form_name string Name of form
	 * @param $form_method string POST or GET
	 * @param $name_array string[] Name array for name attribute
	 * @param $id_name string ID name
	 * @param $column_data object Value for radio button
	 * @param $checked_by_default bool Is this item selected by default?
	 * @param $state DataFormState
	 * @param $label string HTML
	 * @return string
	 */
	public static function format_radio($form_name, $form_method, $name_array, $id_name, $column_data, $checked_by_default, $state, $label)
	{
		if ($state && !is_null($state->find_item($name_array))) {
			$selected_item = $state->find_item($name_array);
			$checked = ((string)$selected_item === (string)$column_data) ? "checked" : "";
		}
		else
		{
			$selected_item = null;
			if ($column_data instanceof Selected) {
				$checked = ($column_data->is_selected() ? "checked" : "");
			}
			elseif ($checked_by_default) {
				$checked = "checked";
			}
			else
			{
				$checked = "";
			}
		}
		$input_name = DataFormState::make_field_name($form_name, $name_array);

		$ret = "";
		if ($label !== "") {
			$ret .= '<label for="' . htmlspecialchars($id_name) . '">' . $label . '</label>';
		}
		$ret .= '<input type="radio" id="' . htmlspecialchars($id_name) . '" name="' . htmlspecialchars($input_name) . '" value="' . htmlspecialchars($column_data) . '" ' . $checked . ' />';
		return $ret;
	}

	public function display($form_name, $form_method, $state)
	{
		return self::format_radio($form_name, $form_method, array($this->name), $this->id, $this->value, $this->checked_by_default, $state, $this->label);
	}

	public function get_placement()
	{
		return $this->placement;
	}

}