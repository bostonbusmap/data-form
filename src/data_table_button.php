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
	 * @param $builder DataTableButtonBuilder
	 * @throws Exception
	 */
	public function __construct($builder) {
		$this->text = $builder->get_text();
		$this->type = $builder->get_type();
		$this->action = $builder->get_form_action();
		$this->behavior = $builder->get_behavior();
		$this->name = $builder->get_name();
		$this->placement = $builder->get_placement();
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
		return '<input type="' . htmlspecialchars($type) . '" name="' . htmlspecialchars($qualified_name) . '" value="' . htmlspecialchars($value) . '" onclick="' . htmlspecialchars($onclick) . '"/>';
	}

	public function get_placement() {
		return $this->placement;
	}
}