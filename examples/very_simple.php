<?php
/**
 * Example to show usage of create_data_form_from_database
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
require_once FILE_BASE_PATH . "/www/browser/lib/data_table/sql_builder.php";

/**
 * @param $state DataFormState
 * @returns DataForm
 */
function make_organisms_table($state) {
	$sql = "SELECT *  FROM organisms";

	// This utility function creates a DataForm. Columns are created with
	// similar names to column names in the database. Sorting, filtering and pagination
	// is applied from the $state. Submits happen to very_simple_submit.php, and
	// the final parameter tells the function to make an additional radio button column
	// with that column's values.

	// This function is meant to provide most common functionality. If you need to customize it,
	// copy the internals of the function and adjust to your liking.
	return create_data_form_from_database($sql, $state, "very_simple_submit.php", "organism_id");
}

try
{
	$state = new DataFormState("browse_organisms", $_GET);

	$form = make_organisms_table($state);

	if ($state->only_display_form()) {
		echo $form->display_form($state);
	}
	else
	{
		gfy_header("Browse organisms", "");
		echo $form->display($state);
	}
}
catch (Exception $e) {
	gfy_header("Browse organisms", "");
	echo "<pre>" . $e . "</pre>";
}