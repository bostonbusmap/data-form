<?php
/**
 * Represents a link for use with DataTable. To use, create a DataTableLink object for each cell in the column.
 * Use DataTableLinkFormatter as an option for the DataTableColumn to display the links
 */
class DataTableLink implements IDataTableWidget {
	/** @var  string */
	protected $link;
	/** @var  string */
	protected $text;
	/**
	 * @var string URL
	 */
	protected $action;
	/**
	 * @var IDataTableBehavior
	 */
	protected $behavior;
	/** @var string  */
	protected $placement;

	// add other parameters as appropriate

	public function __construct($text, $link, $action="", $behavior=null, $placement = self::placement_top) {
		$this->link = $link;
		$this->text = $text;
		$this->action = $action;
		$this->behavior = $behavior;
		$this->placement = $placement;
	}

	public function get_link() {
		return $this->link;
	}

	public function get_text() {
		return $this->text;
	}

	public function display($form_name, $state)
	{
		$link = $this->link;
		$text = $this->text;
		if ($this->behavior) {
			$onclick = $this->behavior->action($form_name, $this->action);
		}
		else
		{
			$onclick = "";
		}
		return "<a href='$link' onclick='$onclick'>$text</a>";
	}

	public function get_placement()
	{
		return $this->placement;
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