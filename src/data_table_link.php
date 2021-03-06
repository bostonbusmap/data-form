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
	/**
	 * @var string Mouseover text
	 */
	protected $title;

	/**
	 * @var string Field name for submitted value, if present
	 */
	protected $name;
	/**
	 * @var string Value to submit when link is clicked, if present
	 */
	protected $value;

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
		$this->title = $builder->get_title();
		$this->name = $builder->get_name();
		$this->value = $builder->get_value();
	}

	public function get_link() {
		return $this->link;
	}

	public function get_text() {
		return $this->text;
	}

	public function get_title() {
		return $this->title;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_value() {
		return $this->value;
	}

	public function display($form_name, $form_method, $remote_url, $state)
	{
		return self::display_link($form_name, $form_method, $this->link, $this->text, $this->behavior, $this->title, array($this->name), $this->value);
	}

	/**
	 * Returns HTML for link
	 *
	 * @param $form_name string Name of form
	 * @param $form_method string Either POST or GET
	 * @param $link string URL for link
	 * @param $text string Text of link
	 * @param $behavior IDataTableBehavior What happens when link is clicked
	 * @param $title string Mouseover title
	 * @param $name_array string[]
	 * @param $value string
	 * @param $dont_escape bool If true, don't escape HTML in $text
	 * @return string HTML
	 */
	public static function display_link($form_name, $form_method, $link, $text, $behavior, $title, $name_array, $value, $dont_escape = false) {
		$ret = '';
		$onclick = '';
		if ($behavior) {
			if ($name_array && $value !== '' && $value !== null) {
				$id = DataFormState::make_field_name($form_name, $name_array);

				$onclick .= '$(DataForm.jq(' . json_encode($id) . ')).attr("value", ' . json_encode($value) . '); ';
				$ret .= DataTableHidden::display_hidden($form_name, $name_array, $id, '', "hidden_submit");
			}

			$onclick .= $behavior->action($form_name, $link, $form_method);
		}
		else
		{
			$onclick = "";
		}
		if ($dont_escape) {
			$escaped_text = $text;
		}
		else {
			$escaped_text = htmlspecialchars($text);
		}
		$ret .= '<a href="' . htmlspecialchars($link) . '" onclick="' . htmlspecialchars($onclick) . '" title="' . htmlspecialchars($title) . '">' . $escaped_text . '</a>';
		return $ret;
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
		$title = $column_data->get_title();
		$name = $column_data->get_name();
		$value = $column_data->get_value();
		return DataTableLink::display_link($form_name, "POST", $link, $text, null, $title, array($name), $value);
	}
}