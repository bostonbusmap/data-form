<?php
/**
 * Example for adding columns to a DataForm via AJAX
 *
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";

/**
 * Create DataForm for example
 *
 * @param DataFormState $state State of form which roughly encapsulates $_POST
 * @return DataForm
 */
function make_form($state) {
	$this_url = $_SERVER['REQUEST_URI'];

	// numbers in $rows will be converted to checkboxes using this formatter
	$checkbox_formatter = new DataTableCheckboxCellFormatter();

	// create first three columns
	$columns = array();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Select")->column_key("perm_1")->cell_formatter($checkbox_formatter)->build();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Permanent Column #1")->column_key("perm_1")->build();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Permanent Column #2")->column_key("perm_2")->build();

	// The Add Column and Remove Column buttons just refresh the table while
	// updating this parameter
	$num_columns = $state->find_item(array("num_columns"));
	if (is_null($num_columns)) {
		$num_columns = 2;
	}
	else
	{
		$num_columns = (int)$num_columns;
	}

	// create all generated columns
	for ($col_num = 2; $col_num < $num_columns; $col_num++) {
		$columns[] = DataTableColumnBuilder::create()->display_header_name("Generated Column #" . ($col_num - 1))->column_key("gen_" . ($col_num - 1))->build();
	}

	// keep value deterministic so numbers don't change between refreshes
	srand(0);

	$num_rows = 15;

	// generate data to display in table.
	$rows = array();
	for ($row_num = 0; $row_num < $num_rows; $row_num++) {
		// write out permanent columns
		$rows[$row_num] = array();
		$rows[$row_num]["perm_1"] = $row_num;
		$rows[$row_num]["perm_2"] = rand();
	}
	for ($col_num = 2; $col_num < $num_columns; $col_num++) {
		for ($row_num = 0; $row_num < $num_rows; $row_num++) {
			// Each key gen_0, gen_1, etc corresponds to the DataTableColumn objects we created earlier
			$rows[$row_num]["gen_" . ($col_num-1)] = rand();
		}
	}

	$form_name = $state->get_form_name();

	// add Add Column and Remove Column buttons
	$widgets = array();

	$num_columns_name = DataFormState::make_field_name($form_name, array("num_columns"));
	$add_column_behavior = new DataTableBehaviorRefresh(array($num_columns_name => ($num_columns + 1)));
	$widgets[] = DataTableButtonBuilder::create()->text("Add Column")->form_action($this_url)->behavior($add_column_behavior)->build();

	if ($num_columns > 2) {
		$remove_column_behavior = new DataTableBehaviorRefresh(array($num_columns_name => ($num_columns - 1)));
		$widgets[] = DataTableButtonBuilder::create()->text("Remove Column")->form_action($this_url)->behavior($remove_column_behavior)->build();
	}

	// create DataTable from rows, buttons, and columns
	$table = DataTableBuilder::create()->columns($columns)->rows($rows)->widgets($widgets)->build();
	// create DataForm from DataTable
	$form = DataFormBuilder::create($state->get_form_name())->tables(array($table))->remote($this_url)->build();
	return $form;
}

try {
	// $state is used to keep form data consistent between refreshes (and to interpret it after submitting the form)

	// Try selecting some checkboxes and add a column. Notice how the checkboxes remain selected.
	$state = new DataFormState("add_columns", $_GET);
	$form = make_form($state);
	if ($state->only_display_form()) {
		// Just asking for a refresh. Only display form HTML
		echo $form->display_form($state);
	}
	else
	{
		gfy_header("Add and remove columns", "");
		echo $form->display($state);
	}
}
catch (Exception $e) {
	echo "<pre>" . $e . "</pre>";
}