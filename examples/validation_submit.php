<?php
/**
 * Validation example using DataForm
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

try {
	$current_state = new DataFormState("select_3", $_GET);

	// Get numbers from current_state and print them
	gfy_header("Show results", "");
	$numbers = $current_state->find_item(array("number"));
	$selected_any = false;
	if ($numbers) {
		foreach ($numbers as $number) {
			echo "Selected item " . $number . "<br />";
			$selected_any = true;
		}
	}

	if (!$selected_any)
	{
		echo "No even numbers selected";
	}
}
catch (Exception $e) {
	echo "<pre>" . $e . "</pre>";
}