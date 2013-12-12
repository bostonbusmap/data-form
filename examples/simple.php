<?php
/**
 * Simple DataForm example
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
 * Create the DataForm object we will use to display
 *
 * @return DataForm
 */
function make_form() {
	// Make two columns: numbers 0 through 14, and another column with that number squared
	// The column_key parameter will be matched up with the key in $rows defined below
	// so the column knows where the data is coming from.
	$columns = array();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Numbers")->column_key("number")->build();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Squared number")->column_key("square")->build();

	// create data to be displayed
	$rows = array();
	for ($i = 0; $i < 15; $i++) {
		$row = array();
		$row["number"] = $i;
		$row["square"] = $i * $i;

		$rows[] = $row;
	}

	// Create DataForm. Unlike most other examples here, this is a local form which means there's no
	// AJAX communication with the server to refresh the table. Column sorting and filtering
	// will be done with Javascript instead, and no pagination will be done
	$table = DataTableBuilder::create()->columns($columns)->rows($rows)->build();
	$form = DataFormBuilder::create("simple")->tables(array($table))->build();
	return $form;
}

gfy_header("Simple table example", "");
try {
	// This is a local form so we are not creating DataFormState for refreshes as in other examples.
	$form = make_form();
	echo $form->display();
}
catch (Exception $e) {
	echo "<pre>" . $e . "</pre>";
}