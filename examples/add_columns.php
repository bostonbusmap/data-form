<?php
/**
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
 * @param DataFormState $state
 * @return DataForm
 */
function make_form($state) {
	$this_url = $_SERVER['REQUEST_URI'];

	$checkbox_formatter = new DataTableCheckboxCellFormatter();

	$columns = array();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Select")->column_key("perm_1")->cell_formatter($checkbox_formatter)->build();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Permanent Column #1")->column_key("perm_1")->build();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Permanent Column #2")->column_key("perm_2")->build();

	$num_columns = $state->find_item(array("num_columns"));
	if (is_null($num_columns)) {
		$num_columns = 2;
	}
	else
	{
		$num_columns = (int)$num_columns;
	}

	for ($j = 2; $j < $num_columns; $j++) {
		$columns[] = DataTableColumnBuilder::create()->display_header_name("Generated Column #" . ($j - 1))->column_key("gen_" . ($j - 1))->build();
	}

	// keep value deterministic so numbers don't change between refreshes
	srand(0);

	$rows = array();
	for ($i = 0; $i < 15; $i++) {
		$row = array();
		$row["perm_1"] = $i;
		$row["perm_2"] = rand();
		for ($j = 2; $j < $num_columns; $j++) {
			$row["gen_" . ($j-1)] = rand();
		}

		$rows[] = $row;
	}

	$form_name = $state->get_form_name();

	$widgets = array();

	$num_columns_name = DataFormState::make_field_name($form_name, array("num_columns"));
	$add_column_behavior = new DataTableBehaviorRefresh(array($num_columns_name => ($num_columns + 1)));
	$widgets[] = DataTableButtonBuilder::create()->name("add_column")->text("Add Column")->form_action($this_url)->behavior($add_column_behavior)->build();

	if ($num_columns > 2) {
		$remove_column_behavior = new DataTableBehaviorRefresh(array($num_columns_name => ($num_columns - 1)));
		$widgets[] = DataTableButtonBuilder::create()->name("add_column")->text("Remove Column")->form_action($this_url)->behavior($remove_column_behavior)->build();
	}

	$table = DataTableBuilder::create()->columns($columns)->rows($rows)->widgets($widgets)->build();
	$form = DataFormBuilder::create($state->get_form_name())->tables(array($table))->remote($this_url)->build();
	return $form;
}

try {
	$state = new DataFormState("add_columns", $_POST);
	$form = make_form($state);
	if ($state->only_display_form()) {
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