<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

/**
 * Rule to validate a form given its state
 */
interface IValidatorRule {
	/**
	 * @param $form DataForm
	 * @param $state DataFormState
	 * @return string Text of the error, or an empty string if no error
	 */
	function validate($form, $state);
}