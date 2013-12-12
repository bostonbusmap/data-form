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
 * Interface meant to represent a function or closure which formats header cells in a certain way
 */
interface IDataTableHeaderFormatter {
	/**
	 * Formats a header for a DataTableColumn
	 *
	 * @param string $form_name Name of HTML form
	 * @param string $column_key Column key
	 * @param object $header_data Something to render as header of column
	 * @param $state DataFormState State which may contain old contents of item. May be null
	 * @return string HTML formatted column data
	 */
	public function format($form_name, $column_key, $header_data, $state);
}