<?php
/**
 * A hidden input
 */
class DataTableHidden implements IDataTableWidget {
	/** @var string */
	protected $value;
	/** @var  string Name of form element */
	protected $name;

	/**
	 * @param $builder DataTableHiddenBuilder
	 * @throws Exception
	 */
	public function __construct($builder) {
		if (!($builder instanceof DataTableHiddenBuilder)) {
			throw new Exception("builder expected to be instance of DataTableHiddenBuilder");
		}
		$this->value = $builder->get_value();
		$this->name = $builder->get_name();
	}

	/**
	 * @param $form_name string Name of form
	 * @param $form_method string GET or POST
	 * @param $state DataFormState
	 * @return string HTML
	 */
	public function display($form_name, $form_method, $state)
	{
		$qualified_name = $form_name . "[" . $this->name . "]";
		$ret = '<input type="hidden" id="' . htmlspecialchars($qualified_name) . '" name="' . htmlspecialchars($qualified_name) . '" value="' . htmlspecialchars($this->value) . '" />';
		return $ret;
	}

	public function get_placement()
	{
		// doesn't matter for a hidden input
		return IDataTableWidget::placement_top;
	}
}