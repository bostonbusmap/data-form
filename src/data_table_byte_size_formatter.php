<?php
/**
 * DataTableByteSizeFormatter
 *
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    Core
 * @copyright  All Authors and the President and Fellows of Harvard University
 */

require_once 'data_table_cell_formatter.php';

class DataTableByteSizeFormatter implements IDataTableCellFormatter {
	
	public function format($form_name, $column_header, $column_data, $rowid, $state) {
		return formatSIPrefix($column_data);
	}

}
