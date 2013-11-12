<?php
class DataTableLinkBuilder {
	/** @var string */
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
	 * @param $text string
	 * @return DataTableLinkBuilder
	 */
	public function text($text) {
		$this->text = $text;
		return $this;
	}

	/**
	 * @param $link string
	 * @return DataTableLinkBuilder
	 */
	public function link($link) {
		$this->link = $link;
		return $this;
	}

	/**
	 * @param $name string
	 * @return DataTableLinkBuilder
	 */
	public function name($name) {
		$this->name = $name;
		return $this;
	}

	/**
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
	 * @return DataTableLink
	 * @throws Exception
	 */
	public function build() {
		if (!$this->name) {
			$this->name = "";
		}
		if (!is_string($this->name)) {
			throw new Exception("name must be a string");
		}

		if (!$this->text) {
			$this->text = "";
		}
		if (!is_string($this->text)) {
			throw new Exception("text must be a string");
		}

		if (!$this->link) {
			$this->link = "";
		}
		if (!is_string($this->link)) {
			throw new Exception("type must be a string");
		}

		if ($this->behavior && !($this->behavior instanceof IDataTableBehavior)) {
			throw new Exception("change_behavior must be instance of IDataTableBehavior");
		}
		if (!$this->placement) {
			$this->placement = IDataTableWidget::placement_top;
		}
		if ($this->placement != IDataTableWidget::placement_top && $this->placement != IDataTableWidget::placement_bottom) {
			throw new Exception("placement must be 'top' or 'bottom'");
		}

		return new DataTableLink($this);
	}
}