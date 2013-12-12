<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

require_once "sql_lib.php";

try {
	$state = new DataFormState("searches", $_POST);
	export_rows($state);
}
catch (Exception $e) {
	echo "<pre>" . $e . "</pre>";
}