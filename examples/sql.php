<?php
/**
 * Example showing use of DataForm with SQL
 *
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

require_once "sql_lib.php";

try {
	// See make_organisms_form in sql_lib.php for more information
	$state = new DataFormState("organisms", $_GET);
	if ($state->only_display_form()) {
		try
		{
			$form = make_organisms_form($state, "sql.php");
			echo $form->display_form($state);
		}
		catch (Exception $e) {
			echo json_encode(array("error" => $e->getMessage()));
		}
	}
	else
	{
		$form = make_organisms_form($state, "sql.php");
		gfy_header("SQL example", "");
		echo $form->display($state);
	}
}
catch (Exception $e) {
	echo "<pre>" . $e->getMessage() . "</pre>";
}