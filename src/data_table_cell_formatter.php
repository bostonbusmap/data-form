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
 * Interface meant to represent a function or closure which formats cells in a certain way
 */
interface IDataTableCellFormatter {
	/**
	 * Formats a cell for a DataTableColumn
	 *
	 * @param string $form_name Name of HTML form
	 * @param string $column_header Column key
	 * @param object $column_data Data to be shown for the cell
	 * @param string $rowid ID for the cell's row
	 * @param DataFormState $state State for data form
	 * @return string HTML formatted column data
	 */
	public function format($form_name, $column_header, $column_data, $rowid, $state);
}