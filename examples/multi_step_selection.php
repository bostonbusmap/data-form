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
require_once "data.php";

/**
 * @param DataFormState $state
 * @return DataForm
 */
function make_form($state) {
	$this_url = HTTP_BASE_PATH . "/browser/lib/data_table/examples/multi_step_selection.php";
	$next_url = HTTP_BASE_PATH . "/browser/lib/data_table/examples/multi_step_selection_2.php";

	$columns = array();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Select")->column_key("city")->
		cell_formatter(new DataTableCheckboxCellFormatter())->build();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("City")->column_key("city")->build();

	$buttons = array();
	$buttons[] = DataTableButtonBuilder::create()->text("Continue >>")->name("submit")->form_action($next_url)->
		behavior(new DataTableBehaviorSubmit())->build();

	$rows = array();
	foreach (get_data() as $obj) {
		$rows[$obj["city"]] = array("city" => $obj["city"]);
	}

	$table = DataTableBuilder::create()->columns($columns)->rows($rows)->widgets($buttons)->
		build();
	$form = DataFormBuilder::create($state->get_form_name())->tables(array($table))->remote($this_url)->build();
	return $form;
}

try {
	$state = new DataFormState("select_cities", $_GET);
	$form = make_form($state);
	if ($state->only_display_form()) {
		echo $form->display_form($state);
	}
	else
	{
		gfy_header("Select cities", "");
		echo $form->display($state);
	}
}
catch (Exception $e) {
	echo "<pre>" . $e . "</pre>";
}