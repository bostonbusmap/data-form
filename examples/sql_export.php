<?php
/**
 * Example exporting SQL with DataForm
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
	// see export_rows() in sql_lib.php for more information
	$state = new DataFormState("organisms", $_GET);
	export_rows($state);
}
catch (Exception $e) {
	echo "<pre>" . $e . "</pre>";
}