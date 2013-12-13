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
require_once FILE_BASE_PATH . "/www/browser/lib/data_table/sql_builder.php";

$state = new DataFormState("browse_organisms", $_GET);

gfy_header("Browse organisms", "");

$data = $state->get_form_data();
if (array_key_exists("organism_id", $data)) {
	echo "Selected organism #" . $data["organism_id"];
}
else
{
	echo "No search selected";
}