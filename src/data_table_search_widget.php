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
 * Widget for search control which is displayed outside the DataTable
 */
class DataTableSearchWidget implements IDataTableWidget {

	/** @var  IDataTableSearchFormatter */
	protected $formatter;

	/** @var string Where search textbox will be displayed relative to DataTable */
	protected $placement;

	/** @var string Where to submit to */
	protected $form_action;

	/** @var  string Name of table if any */
	protected $table_name;

	/** @var  string Column key */
	protected $column_key;

	/** @var  DataTableSearchState Default value for search textbox */
	protected $default_value;

	/** @var string Label with HTML */
	protected $label;

	/**
	 * Use DataTableSearchWidgetBuilder::build()
	 *
	 * @param $builder
	 * @throws Exception
	 */
	public function __construct($builder) {
		if (!($builder instanceof DataTableSearchWidgetBuilder)) {
			throw new Exception("builder must be of type DataTableSearchWidgetBuilder");
		}

		$search_type = $builder->get_search_type();
		switch ($search_type) {
			case DataTableSearchState::like:
			case DataTableSearchState::rlike:
			case DataTableSearchState::in:
				$this->formatter = new TextboxSearchFormatter($search_type);
				break;
			case DataTableSearchState::greater_than:
			case DataTableSearchState::greater_or_equal:
			case DataTableSearchState::less_than:
			case DataTableSearchState::less_or_equal:
			case DataTableSearchState::equal:
				$this->formatter = new NumericalSearchFormatter();
				break;
			default:
				throw new Exception("Unknown search type");
		}

		$this->placement = $builder->get_placement();
		$this->form_action = $builder->get_form_action();
		$this->table_name = $builder->get_table_name();
		$this->column_key = $builder->get_column_key();
		$this->default_value = $builder->get_default_value();
		$this->label = $builder->get_label();
	}

	/**
	 * Render search widget HTML
	 *
	 * @param $form_name string Name of form
	 * @param $form_method string GET or POST
	 * @param $state DataFormState
	 * @return string HTML
	 */
	public function display($form_name, $form_method, $state)
	{
		return $this->formatter->format($form_name, $this->form_action, $form_method, $this->table_name, $this->column_key, $state, $this->default_value, $this->label);
	}

	public function get_placement()
	{
		return $this->placement;
	}
}