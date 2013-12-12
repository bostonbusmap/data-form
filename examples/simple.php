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
 * @return DataForm
 */
function make_form() {
	$columns = array();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Numbers")->column_key("number")->build();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Squared number")->column_key("square")->build();

	$rows = array();
	for ($i = 0; $i < 15; $i++) {
		$row = array();
		$row["number"] = $i;
		$row["square"] = $i * $i;

		$rows[] = $row;
	}

	$table = DataTableBuilder::create()->columns($columns)->rows($rows)->build();
	$form = DataFormBuilder::create("simple")->tables(array($table))->build();
	return $form;
}

gfy_header("Simple table example", "");
try {
	$form = make_form();
	echo $form->display();
}
catch (Exception $e) {
	echo "<pre>" . $e . "</pre>";
}