<?php

require_once "data_table_widget.php";

/**
 * A widget which displays a button for submitting a DataTable form or resetting it
 */
class DataTableButton implements IDataTableWidget {
	/** @var string  */
	private $text;
	/**
	 * @var string
	 */
	private $action;
	/** @var  string */
	private $name;
	/** @var  string */
	private $type;
	/**
	 * @var IDataTableBehavior
	 */
	private $behavior;

	/**
	 * @var string
	 */
	private $placement;

	/**
	 * @param string $text Text to display
	 * @param string $name Name of button
	 * @param string $action URL to submit to
	 * @param IDataTableBehavior $behavior What happens when button is clicked
	 * @param string $type Type of input (usually reset or submit)
	 * @param string $placement Where button goes (currently either "top" or "bottom")
	 * @throws Exception
	 */
	public function __construct($text, $name, $action, $behavior = null, $type = "submit", $placement = self::placement_top) {
		$this->name = $name;
		$this->action = $action;
		$this->type = $type;
		if ($behavior && !($behavior instanceof IDataTableBehavior)) {
			throw new Exception("Must specify behavior, must be instance of IDataTableBehavior");
		}
		$this->behavior = $behavior;

		$this->text = $text;

		if ($placement != self::placement_top && $placement != self::placement_bottom) {
			throw new Exception("placement must be 'top' or 'bottom'");
		}
		$this->placement = $placement;
	}

	public function display($form_name, $form_method, $state=null)
	{
		$value = $this->text;
		$qualified_name = $form_name . "[" . $this->name . "]";
		if ($this->behavior) {
			$onclick = $this->behavior->action($form_name, $this->action, $form_method);
		}
		else
		{
			$onclick = '';
		}
		$type = $this->type;
		return "<input type='$type' name='$qualified_name' value='$value' onclick='" . $onclick . "'/>";
	}

	public function get_placement() {
		return $this->placement;
	}
}