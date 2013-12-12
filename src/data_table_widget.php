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
 * Piece of HTML displayed above or below the table
 */
interface IDataTableWidget {
	const placement_top = "top";
	const placement_bottom = "bottom";

	/**
	 * Returns HTML to display widget
	 *
	 * @param $form_name string Name of form
	 * @param $form_method string GET or POST
	 * @param $state DataFormState State which may contain widget state
	 * @return string HTML
	 */
	public function display($form_name, $form_method, $state);

	/**
	 * Describes where widget will be rendered relative to textbox
	 *
	 * @return string See IDataTableWidget constants for possible values
	 */
	public function get_placement();
}
