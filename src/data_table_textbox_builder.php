<?php
class DataTableTextboxBuilder
{
	/** @var string */
	protected $text;
	/** @var  string Name of form element */
	protected $name;
	/** @var  string */
	protected $form_action;
	/** @var  IDataTableBehavior */
	protected $behavior;
	/** @var  string */
	protected $placement;

	/**
	 * @return DataTableTextboxBuilder
	 */
	public static function create()
	{
		return new DataTableTextboxBuilder();
	}

	/**
	 * @param $text string
	 * @return DataTableTextboxBuilder
	 */
	public function text($text)
	{
		$this->text = $text;
		return $this;
	}

	/**
	 * @param $name string
	 * @return DataTableTextboxBuilder
	 */
	public function name($name)
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @param $form_action string
	 * @return DataTableTextboxBuilder
	 */
	public function form_action($form_action)
	{
		$this->form_action = $form_action;
		return $this;
	}

	/**
	 * @param $behavior IDataTableBehavior
	 * @return DataTableTextboxBuilder
	 */
	public function behavior($behavior)
	{
		$this->behavior = $behavior;
		return $this;
	}

	/**
	 * @param $placement string
	 * @return DataTableTextboxBuilder
	 */
	public function placement($placement)
	{
		$this->placement = $placement;
		return $this;
	}

	/**
	 * @return string
	 */
	public function get_text()
	{
		return $this->text;
	}

	/**
	 * @return string
	 */
	public function get_name()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function get_form_action()
	{
		return $this->form_action;
	}

	/**
	 * @return IDataTableBehavior
	 */
	public function get_behavior()
	{
		return $this->behavior;
	}

	/**
	 * @return string
	 */
	public function get_placement()
	{
		return $this->placement;
	}

	/**
	 * @return DataTableTextbox
	 * @throws Exception
	 */
	public function build()
	{
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

		if ($this->form_action && !is_string($this->form_action)) {
			throw new Exception("form_action must be a string");
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

		return new DataTableTextbox($this);
	}
}
