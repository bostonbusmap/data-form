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
	 * @var bool
	 */
	protected $ignore_pagination;
	/**
	 * @var bool
	 */
	protected $ignore_filtering;

	/**
	 * @var IPaginationInfo
	 */
	protected $pagination_info;

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

	public function pagination_info($pagination_info) {
		$this->pagination_info = $pagination_info;
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
		$this->ignore_pagination = $ignore_pagination;
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
		$this->ignore_filtering = $ignore_filtering;
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

		if ($this->state !== null && !($this->state instanceof DataFormState)) {
			throw new Exception("state must be instance of DataFormState");
		}

		if ($this->settings !== null && !($this->settings instanceof DataTableSettings)) {
			throw new Exception("settings must be DataTableSettings");
		}

		if ($this->pagination_info !== null && !($this->pagination_info instanceof IPaginationInfo)) {
			throw new Exception("pagination_info must be instance of IPaginationInfo");
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
		if (!($this->pagination_transform instanceof ISQLTreeTransformWithCount)) {
			throw new Exception("Pagination transform must be instance of ISQLTreeTransformWithCount");
		}

		if (is_null($this->count_transform)) {
			$this->count_transform = new CountTreeTransform();
		}
		if (!($this->count_transform instanceof ISQLTreeTransform)) {
			throw new Exception("Count transform must be instance of ISQLTreeTransform");
		}

		if (is_null($this->sort_transform)) {
			$this->sort_transform = new SortTreeTransform();
		}
		if (!($this->sort_transform instanceof ISQLTreeTransform)) {
			throw new Exception("Sort transform must be instance of ISQLTreeTransform");
		}

		if (is_null($this->filter_transform)) {
			$this->filter_transform = new FilterTreeTransform();
		}
		if (!($this->filter_transform instanceof ISQLTreeTransform)) {
			throw new Exception("Filter transform must be instance of ISQLTreeTransform");
		}

		if ($this->ignore_pagination === null) {
			$this->ignore_pagination = false;
		}
		if (!is_bool($this->ignore_pagination)) {
			throw new Exception("ignore_pagination must be a bool");
		}

		if ($this->ignore_filtering === null) {
			$this->ignore_filtering = false;
		}
		if (!is_bool($this->ignore_filtering)) {
			throw new Exception("ignore_filtering must be a bool");
		}
	}

	/**
	 * Create SQL from state and clauses
	 *
	 * @return string SQL (not escaped!)
	 * @throws Exception
	 */
	public function build() {
		$this->validate_input();

		$tree = $this->sql_tree;

		$pagination_info = $this->make_pagination_info();

		if (!$this->ignore_filtering) {
			$tree = $this->filter_transform->alter($tree, $pagination_info);
		}
		$tree = $this->sort_transform->alter($tree, $pagination_info);
		if (!$this->ignore_pagination) {
			if ($pagination_info instanceof PaginationInfoWithCount) {
				$num_rows = $pagination_info->get_row_count();
			}
			elseif ($this->settings === null || $this->settings->get_total_rows() === null) {
				throw new Exception("SQLBuilder->settings must contain the number of rows in order to paginate properly");
			}
			else
			{
				$num_rows = $this->settings->get_total_rows();
			}

			$pagination_info_with_count = new PaginationInfoWithCount($pagination_info, $num_rows);
			$tree = $this->pagination_transform->alter($tree, $pagination_info_with_count);
		}

		$creator = new PHPSQLCreator();
		return $creator->create($tree);
	}

	/**
	 * Create SQL for counting the number of rows. This changes the SQL to 'SELECT COUNT(*) FROM ...'
	 * @throws UnableToCreateSQLException
	 * @throws UnsupportedFeatureException
	 * @throws Exception
	 * @return string SQL
	 */
	public function build_count() {
		$this->validate_input();

		$tree = $this->sql_tree;

		$pagination_info = $this->make_pagination_info();
		// order is important here. The count transform puts everything within a subquery so it must go last
		if (!$this->ignore_filtering) {
			$tree = $this->filter_transform->alter($tree, $pagination_info);
		}
		$tree = $this->count_transform->alter($tree, $pagination_info);

		$creator = new PHPSQLCreator();
		return $creator->create($tree);
	}

	/**
	 * @return PaginationInfo
	 */
	private function make_pagination_info() {
		if ($this->pagination_info !== null) {
			return $this->pagination_info;
		}
		else {
			return DataFormState::make_pagination_info($this->state, $this->settings, $this->table_name);
		}
	}

	/**
	 * @param string $conn_type string
	 * @param string $rowid_key string
	 * @throws UnableToCreateSQLException
	 * @throws UnsupportedFeatureException
	 * @throws Exception
	 * @return array Iterator, num_rows
	 */
	public function obtain_paginated_data_and_row_count($conn_type, $rowid_key) {
		$count_sql = $this->build_count();
		$count_res = gfy_db::query($count_sql, $conn_type, true);
		$count_row = gfy_db::fetch_row($count_res);
		$num_rows = (int)$count_row[0];

		$pagination_info = $this->make_pagination_info();
		$pagination_info_with_count = new PaginationInfoWithCount($pagination_info, $num_rows);

		// cloning so we don't have to reparse SQL which takes a little while
		$sql_builder = clone $this;
		$sql_builder->pagination_info = $pagination_info_with_count;
		$paginated_sql = $sql_builder->build();

		$iterator = new DatabaseIterator($paginated_sql, $conn_type, $rowid_key);
		return array($iterator, $num_rows);
	}
}