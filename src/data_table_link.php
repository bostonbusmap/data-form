<?php
/**
 * Represents a link for use with DataTable. To use, create a DataTableLink object for each cell in the column.
 * Use DataTableLinkFormatter as an option for the DataTableColumn to display the links
 */
class DataTableLink {
	protected $link;
	protected $text;

	// add other parameters as appropriate

	public function __construct($link, $text) {
		$this->link = $link;
		$this->text = $text;
	}

	public function get_link() {
		return $this->link;
	}

	public function get_text() {
		return $this->text;
	}
}

class DataTableLinkFormatter implements IDataTableCellFormatter {

	/**
	 * Implementation to display a link
	 *
	 * WARNING: do not remove parameters, this uses reflection to call the function
	 * @param string $form_name The name of the form
	 * @param string $column_header Unused
	 * @param DataTableLink $column_data The link data
	 * @param int $rowid Row id number
	 * @param DataFormState $state Unused
	 * @return string HTML for a link
	 */
	public function format($form_name, $column_header, $column_data, $rowid, $state) {
		// TODO: sanitize for HTML
		$text = $column_data->get_text();
		$link = $column_data->get_link();
		return "<a href='$link'>$text</a>";
	}
}