<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */
require_once "data_form.php";
require_once "sql_tree_transform.php";
require_once "paginator.php";
require_once FILE_BASE_PATH . "/lib/PHP-SQL-Parser/PHPSQLCreator.php";
require_once FILE_BASE_PATH . "/lib/PHP-SQL-Parser/PHPSQLParser.php";

/**
 * Alters SQL using information from special fields in DataFormState for pagination, sorting and filtering
 */
class SQLBuilder implements IPaginator {
	/**
	 * @var array Abstract syntax tree created by PHP-SQL-Parser. Must only be modified by constructor
	 */
	protected $sql_tree;

	/** @var  DataFormState State containing table information */
	protected $state;

	/** @var  string Name of HTML table, if any */
	protected $table_name;

	/** @var  DataTableSettings Default settings for table */
	protected $settings;

	/**
	 * @var ISQLTreeTransform How to alter SQL to represent pagination
	 */
	protected $pagination_transform;
	/**
	 * @var ISQLTreeTransform  How to alter SQL to represent filtering
	 */
	protected $filter_transform;
	/**
	 * @var ISQLTreeTransform  How to alter SQL to represent sorting
	 */
	protected $sort_transform;
	/**
	 * @var ISQLTreeTransform  How to alter SQL to represent counting
	 */
	protected $count_transform;

	/**
	 * Create SQLBuilder. This parses the SQL into a tree for further modification
	 *
	 * @param $sql string SQL to parse
	 * @throws Exception
	 */
	public function __construct($sql) {
		if ($sql) {
			if (!is_string($sql)) {
				throw new Exception("sql parameter must be a string");
			}
			$parser = new PHPSQLParser();
			$this->sql_tree = $parser->parse($sql);
		}
		else
		{
			$this->sql_tree = array();
		}
	}


	/**
	 * Same as constructor, allows chaining of method calls
	 *
	 * @param $sql string SQL
	 * @return SQLBuilder
	 */
	public static function create($sql) {
		return new SQLBuilder($sql);
	}

	/**
	 * @param $table_name string
	 * @return SQLBuilder
	 */
	public function table_name($table_name) {
		$this->table_name = $table_name;
		return $this;
	}

	/**
	 * @param $state DataFormState
	 * @return SQLBuilder
	 */
	public function state($state) {
		$this->state = $state;
		return $this;
	}

	/**
	 * @param $settings DataTableSettings
	 * @return SQLBuilder
	 */
	public function settings($settings) {
		$this->settings = $settings;
		return $this;
	}

	/**
	 * @param $pagination_transform ISQLTreeTransform
	 * @return SQLBuilder
	 */
	public function pagination_transform($pagination_transform) {
		$this->pagination_transform = $pagination_transform;
		return $this;
	}
	/**
	 * @param $filter_transform ISQLTreeTransform
	 * @return SQLBuilder
	 */
	public function filter_transform($filter_transform) {
		$this->filter_transform = $filter_transform;
		return $this;
	}
	/**
	 * @param $sort_transform ISQLTreeTransform
	 * @return SQLBuilder
	 */
	public function sort_transform($sort_transform) {
		$this->sort_transform = $sort_transform;
		return $this;
	}
	/**
	 * @param $count_transform ISQLTreeTransform
	 * @return SQLBuilder
	 */
	public function count_transform($count_transform) {
		$this->count_transform = $count_transform;
		return $this;
	}

	/**
	 * If true, no LIMIT or OFFSET clauses will be added
	 *
	 * @param bool $ignore_pagination
	 * @return $this|SQLBuilder
	 */
	public function ignore_pagination($ignore_pagination = true)
	{
		if ($ignore_pagination) {
			$this->pagination_transform = new IdentityTreeTransform();
		}
		else
		{
			$this->pagination_transform = new LimitPaginationTreeTransform();
		}
		return $this;
	}

	/**
	 * If true, no filtering clauses will be added (typically WHERE clauses)
	 *
	 * Note that this just calls filter_transform so make sure you aren't doing both
	 * @param $ignore_filtering bool
	 * @return SQLBuilder
	 */
	public function ignore_filtering($ignore_filtering = true)
	{
		if ($ignore_filtering) {
			$this->filter_transform = new IdentityTreeTransform();
		}
		else
		{
			$this->filter_transform = new FilterTreeTransform();
		}
		return $this;
	}

	/**
	 * Validate input. Throws an exception if input is not valid
	 * @throws Exception
	 */
	protected function validate_input() {
		if (is_null($this->table_name)) {
			$this->table_name = "";
		}
		if (!is_string($this->table_name)) {
			throw new Exception("table_name must be a string");
		}

		if ($this->state && !($this->state instanceof DataFormState)) {
			throw new Exception("state must be instance of DataFormState");
		}

		if ($this->settings && !($this->settings instanceof DataTableSettings)) {
			throw new Exception("settings must be DataTableSettings");
		}

		if (!is_array($this->sql_tree)) {
			throw new Exception("sql_tree has not been defined");
		}
		if (array_key_exists("LIMIT", $this->sql_tree) && $this->settings && $this->settings->uses_pagination()) {
			throw new Exception("The LIMIT clause is added automatically when paginating so it shouldn't be present in statement yet");
		}

		if (is_null($this->pagination_transform)) {
			$this->pagination_transform = new LimitPaginationTreeTransform();
		}
		if (!($this->pagination_transform instanceof ISQLTreeTransform)) {
			throw new Exception("Pagination transform must be instance of ISQLTreeFilter");
		}

		if (is_null($this->count_transform)) {
			$this->count_transform = new CountTreeTransform();
		}
		if (!($this->count_transform instanceof ISQLTreeTransform)) {
			throw new Exception("Count transform must be instance of ISQLTreeFilter");
		}

		if (is_null($this->sort_transform)) {
			$this->sort_transform = new SortTreeTransform();
		}
		if (!($this->sort_transform instanceof ISQLTreeTransform)) {
			throw new Exception("Sort transform must be instance of ISQLTreeFilter");
		}

		if (is_null($this->filter_transform)) {
			$this->filter_transform = new FilterTreeTransform();
		}
		if (!($this->filter_transform instanceof ISQLTreeTransform)) {
			throw new Exception("Filter transform must be instance of ISQLTreeFilter");
		}

	}

	/**
	 * Create SQL from state and clauses
	 *
	 * @return string SQL (not escaped!)
	 * @throws Exception
	 */
	public function obtain_paginated_data() {
		$this->validate_input();

		$tree = $this->sql_tree;

		$tree = $this->filter_transform->alter($tree, $this->state, $this->settings, $this->table_name);
		$tree = $this->sort_transform->alter($tree, $this->state, $this->settings, $this->table_name);
		$tree = $this->pagination_transform->alter($tree, $this->state, $this->settings, $this->table_name);

		$creator = new PHPSQLCreator();
		return $creator->create($tree);
	}

	/**
	 * Create SQL for counting the number of rows. This changes the SQL to 'SELECT COUNT(*) FROM ...'
	 * @return string SQL
	 */
	public function obtain_row_count() {
		$this->validate_input();

		$tree = $this->sql_tree;

		// order is important here. The count transform puts everything within a subquery so it must go last
		$tree = $this->filter_transform->alter($tree, $this->state, $this->settings, $this->table_name);
		$tree = $this->count_transform->alter($tree, $this->state, $this->settings, $this->table_name);

		$creator = new PHPSQLCreator();
		return $creator->create($tree);
	}

	public function obtain_paginated_data_and_row_count() {
		return array($this->obtain_paginated_data(), $this->obtain_row_count());
	}

	/**
	 * @return string
	 */
	public function build() {
		return $this->obtain_paginated_data();
	}

	/**
	 * @return string
	 */
	public function build_count() {
		return $this->obtain_row_count();
	}
}