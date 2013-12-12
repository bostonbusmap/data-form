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
 * Builder for DataTableLink
 */
class DataTableLinkBuilder {
	/** @var string Text for link. Unsanitized HTML! */
	protected $text;
	/**
	 * @var string Note that this is also where the form is submitted if the behavior is set accordingly
	 */
	protected $link;
	/** @var  string Name of form element */
	protected $name;
	/** @var  IDataTableBehavior */
	protected $behavior;
	/** @var  string */
	protected $placement;

	/**
	 * @return DataTableLinkBuilder
	 */
	public static function create() {
		return new DataTableLinkBuilder();
	}

	/**
	 * Text for link. Unsanitized HTML!
	 *
	 * @param $text string
	 * @return DataTableLinkBuilder
	 */
	public function text($text) {
		$this->text = $text;
		return $this;
	}

	/**
	 * URL for link target or form action, depending on what the behavior is
	 * @param $link string
	 * @return DataTableLinkBuilder
	 */
	public function link($link) {
		$this->link = $link;
		return $this;
	}

	/**
	 * Field name (becomes form_name[name])
	 *
	 * @param $name string
	 * @return DataTableLinkBuilder
	 */
	public function name($name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * What happens when link is clicked
	 *
	 * @param $behavior IDataTableBehavior
	 * @return DataTableLinkBuilder
	 */
	public function behavior($behavior) {
		$this->behavior = $behavior;
		return $this;
	}

	/**
	 * @param $placement string
	 * @return DataTableLinkBuilder
	 */
	public function placement($placement) {
		$this->placement = $placement;
		return $this;
	}

	/**
	 * @return string
	 */
	public function get_link() {
		return $this->link;
	}

	/**
	 * @return string
	 */
	public function get_text() {
		return $this->text;
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * @return IDataTableBehavior
	 */
	public function get_behavior() {
		return $this->behavior;
	}

	/**
	 * @return string
	 */
	public function get_placement() {
		return $this->placement;
	}

	/**
	 * Validate input and create DataTableLink
	 *
	 * @return DataTableLink
	 * @throws Exception
	 */
	public function build() {
		if (is_null($this->name)) {
			$this->name = "";
		}
		if (!is_string($this->name)) {
			throw new Exception("name must be a string");
		}

		if (is_null($this->text)) {
			$this->text = "";
		}
		if (!is_string($this->text)) {
			throw new Exception("text must be a string");
		}

		if (is_null($this->link)) {
			$this->link = "";
		}
		if (!is_string($this->link)) {
			throw new Exception("type must be a string");
		}

		if ($this->behavior && !($this->behavior instanceof IDataTableBehavior)) {
			throw new Exception("change_behavior must be instance of IDataTableBehavior");
		}
		if (is_null($this->placement)) {
			$this->placement = IDataTableWidget::placement_top;
		}
		if ($this->placement != IDataTableWidget::placement_top && $this->placement != IDataTableWidget::placement_bottom) {
			throw new Exception("placement must be 'top' or 'bottom'");
		}

		return new DataTableLink($this);
	}
}