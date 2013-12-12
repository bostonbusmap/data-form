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
 * Represents a link for use with DataTable. To use, create a DataTableLink object for each cell in the column.
 * Use DataTableLinkFormatter as an option for the DataTableColumn to display the links
 */
class DataTableLink implements IDataTableWidget {
	/**
	 * @var string Note that this doubles as the form action if a behavior is used on the link
	 */
	protected $link;
	/** @var  string Link text (unsanitized HTML!) */
	protected $text;
	/**
	 * @var IDataTableBehavior What happens when the link is clicked
	 */
	protected $behavior;
	/** @var string Where link goes relative to DataTable */
	protected $placement;

	// add other parameters as appropriate

	/**
	 * @param $builder DataTableLinkBuilder
	 * @throws Exception
	 */
	public function __construct($builder) {
		if (!($builder instanceof DataTableLinkBuilder)) {
			throw new Exception("builder expected to be instance of DataTableLinkBuilder");
		}
		$this->link = $builder->get_link();
		$this->text = $builder->get_text();
		$this->behavior = $builder->get_behavior();
		$this->placement = $builder->get_placement();
	}

	public function get_link() {
		return $this->link;
	}

	public function get_text() {
		return $this->text;
	}

	public function display($form_name, $form_method, $state)
	{
		return self::display_link($form_name, $form_method, $this->link, $this->text, $this->behavior);
	}

	/**
	 * Returns HTML for link
	 *
	 * @param $form_name string Name of form
	 * @param $form_method string Either POST or GET
	 * @param $link string URL for link
	 * @param $text string Text of link
	 * @param $behavior IDataTableBehavior What happens when link is clicked
	 * @return string HTML
	 */
	public static function display_link($form_name, $form_method, $link, $text, $behavior) {
		if ($behavior) {
			$onclick = $behavior->action($form_name, $link, $form_method);
		}
		else
		{
			$onclick = "";
		}
		return '<a href="' . htmlspecialchars($link) . '" onclick="' . htmlspecialchars($onclick) . '">' . $text . '</a>';
	}

	public function get_placement()
	{
		return $this->placement;
	}
}

/**
 * Displays DataTableLink objects as HTML links which exist in cells for this column
 */
class DataTableLinkFormatter implements IDataTableCellFormatter {

	/**
	 * Implementation to display a link
	 *
	 * @param string $form_name The name of the form
	 * @param string $column_header Unused
	 * @param DataTableLink $column_data The link data
	 * @param int $rowid Row id number
	 * @param DataFormState $state Unused
	 * @return string HTML for a link
	 * @throws Exception
	 */
	public function format($form_name, $column_header, $column_data, $rowid, $state) {
		if (!($column_data instanceof DataTableLink)) {
			throw new Exception("column_data expected to be instance of DataTableLink");
		}
		$text = $column_data->get_text();
		$link = $column_data->get_link();
		return DataTableLink::display_link($form_name, "POST", $link, $text, null);
	}
}