<?php
/**
 * Example of multiple step forms (step 1)
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
// this require contains information about the cities and zip codes in Massachusetts
require_once "data.php";

/**
 * Make the DataForm object for multi_step_selection.php
 * @param DataFormState $state
 * @return DataForm
 */
function make_form($state) {
	$this_url = HTTP_BASE_PATH . "/browser/lib/data_table/examples/multi_step_selection.php";
	$next_url = HTTP_BASE_PATH . "/browser/lib/data_table/examples/multi_step_selection_2.php";

	$settings = DataTableSettingsBuilder::create()
		->no_pagination()
		->build();

	// Create two columns: a checkbox column and the name of the city
	// Note that both columns have the same column_key: city
	// This is allowed and means that both columns get the same data
	$columns = array();
	$columns[] = DataTableColumnBuilder::create()
		->display_header_name("Select")
		->column_key("city")
		->cell_formatter(new DataTableCheckboxCellFormatter())
		->build();
	$columns[] = DataTableColumnBuilder::create()
		->display_header_name("City")
		->column_key("city")
		->build();

	// simple continue button to go to next form
	$buttons = array();
	$buttons[] = DataTableButtonBuilder::create()
		->text("Continue >>")
		->name("submit")
		->form_action($next_url)
		->behavior(new DataTableBehaviorSubmit())
		->build();

	// fill in data on Massachusetts cities
	$rows = array();
	foreach (get_data() as $obj) {
		$rows[$obj["city"]] = array("city" => $obj["city"]);
	}

	// create a DataTable with the information we just specified
	$table = DataTableBuilder::create()
		->columns($columns)
		->rows($rows)
		->widgets($buttons)
		->settings($settings)
		->build();
	// create a DataForm from the DataTable
	$form = DataFormBuilder::create($state->get_form_name())
		->tables(array($table))
		->remote($this_url)
		->build();
	return $form;
}

try {
	// We can use StdoutWriter to output the form directly to stdout,
	// avoiding the need for a large intermediate string
	$writer = new StdoutWriter();

	// $state here is mostly used to keep state if we refresh the form via AJAX
	// However we don't do that in this particular case, but it's a good idea in general anyway.
	$state = new DataFormState("select_cities", $_GET);
	if ($state->only_display_form()) {
		try
		{
			$form = make_form($state);
			$form->display_form_using_writer($writer, $state);
		}
		catch (Exception $e) {
			echo json_encode(array("error" => $e->getMessage()));
		}
	}
	else
	{
		$form = make_form($state);
		gfy_header("Select cities", "");
		$form->display_using_writer($writer, $state);
	}
}
catch (Exception $e) {
	echo "<pre>" . $e . "</pre>";
}