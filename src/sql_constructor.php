<?php

require_once "paginator.php";

/**
 * Class to build pieces of SQL and allow trivial manipulations, similar to ORM syntax
 *
 * This is just a quick and dirty SQL string concatenator to avoid the cost of parsing SQL
 */
class SqlConstructor implements IPaginator {
	/**
	 * @var array Each item is (clause, alias or null)
	 */
	protected $select_items;
	/**
	 * @var boolean
	 */
	protected $is_distinct;
	/**
	 * @var string[]
	 */
	protected $alias_lookup;
	/**
	 * @var string
	 */
	protected $from_table;
	/**
	 * @var string[]
	 */
	protected $where_items;

	/**
	 * @var string[]
	 */
	protected $joins;
	/**
	 * @var string
	 */
	protected $limit_offset_clause;
	/**
	 * @var string[]
	 */
	protected $group_by_columns;
	/**
	 * @var string
	 */
	protected $order_by_clause;

	/**
	 * @var string
	 */
	protected $table;
	/**
	 * @var DataFormState
	 */
	protected $state;
	/**
	 * @var DataTableSettings
	 */
	protected $settings;
	/**
	 * @var bool
	 */
	protected $ignore_pagination;
	/**
	 * @var bool
	 */
	protected $ignore_filtering;

	public function __construct($copy = null) {
		if ($copy !== null && !($copy instanceof SqlConstructor)) {
			throw new Exception("copy must be instance of SqlConstructor");
		}

		$this->select_items = array();
		$this->where_items = array();
		$this->joins = array();
		$this->group_by_columns = array();
		$this->alias_lookup = array();
		if ($copy !== null) {
			$this->table = $copy->table;
			$this->state = $copy->state;
			$this->settings = $copy->settings;
			$this->order_by_clause = $copy->order_by_clause;
			$this->group_by_columns = $copy->group_by_columns;
			$this->limit_offset_clause = $copy->limit_offset_clause;
			$this->joins = $copy->joins;
			$this->where_items = $copy->where_items;
			$this->select_items = $copy->select_items;
			$this->is_distinct = $copy->is_distinct;
			$this->from_table = $copy->from_table;
			$this->ignore_filtering = $copy->ignore_filtering;
			$this->ignore_pagination = $copy->ignore_pagination;
		}
	}

	/**
	 * @param SqlConstructor|null $copy
	 * @return SqlConstructor
	 */
	public static function create($copy = null) {
		return new SqlConstructor($copy);
	}

	/**
	 * @param $clause string SELECT clause, excluding anything after AS
	 * @param $as string|null
	 * @return $this SqlConstructor
	 * @throws Exception
	 */
	public function add_select_item($clause, $as = null) {
		$this->select_items[] = array($clause, $as);
		if ($as !== null && (!is_string($as) || trim($as) === "")) {
			throw new Exception("as must be a string");
		}
		if ($as !== null) {
			$this->alias_lookup[$as] = $clause;
		}
		return $this;
	}

	/**
	 * @param $item string
	 * @return $this SqlConstructor
	 * @throws Exception
	 */
	public function add_where_item($item) {
		$this->where_items[] = $item;
		return $this;
	}

	/**
	 * @param $item string
	 * @return $this SqlConstructor
	 * @throws Exception
	 */
	public function add_join($item) {
		$this->joins[] = $item;
		return $this;
	}

	/**
	 * @param $group_column string
	 * @return $this SqlConstructor
	 * @throws Exception
	 */
	public function add_group_column($group_column) {
		$this->group_by_columns[] = $group_column;
		return $this;
	}

	/**
	 * @param $order_clause string
	 * @return $this SqlConstructor
	 * @throws Exception
	 */
	public function order_clause($order_clause) {
		$this->order_by_clause = $order_clause;
		return $this;
	}

	/**
	 * @param $limit_offset_clause string
	 * @return $this SqlConstructor
	 * @throws Exception
	 */
	public function limit_offset_clause($limit_offset_clause) {
		$this->limit_offset_clause = $limit_offset_clause;
		return $this;
	}

	/**
	 * FROM table name
	 * @param $table string
	 * @return $this SqlConstructor
	 * @throws Exception
	 */
	public function from($table) {
		$this->from_table = $table;
		return $this;
	}

	/**
	 * The DataForm table used with the DataFormState
	 * @param $table string
	 * @return $this SqlConstructor
	 */
	public function table_name($table) {
		$this->table = $table;
		return $this;
	}

	/**
	 * @param $state DataFormState
	 * @return $this SqlConstructor
	 */
	public function state($state) {
		$this->state = $state;
		return $this;
	}

	/**
	 * @param $settings DataTableSettings
	 * @return $this SqlConstructor
	 */
	public function settings($settings) {
		$this->settings = $settings;
		return $this;
	}

	/**
	 * @param $distinct bool
	 * @return $this SqlConstructor
	 */
	public function distinct($distinct = true) {
		$this->is_distinct = $distinct;
		return $this;
	}

	/**
	 * @param $ignore_pagination bool
	 * @return $this SqlConstructor
	 */
	public function ignore_pagination($ignore_pagination = true) {
		$this->ignore_pagination = $ignore_pagination;
		return $this;
	}

	/**
	 * @param $ignore_filtering bool
	 * @return $this SqlConstructor
	 */
	public function ignore_filtering($ignore_filtering = true) {
		$this->ignore_filtering = $ignore_filtering;
		return $this;
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	protected function validate_input() {
		if (!is_string($this->from_table) || trim($this->from_table) === "") {
			throw new Exception("table name not specified");
		}
		if (count($this->select_items) === 0) {
			throw new Exception("No select columns specified");
		}
		if (!is_string($this->from_table) || trim($this->from_table) === "") {
			throw new Exception("FROM table must be a string");
		}
		if ($this->limit_offset_clause === null) {
			$this->limit_offset_clause = "";
		}
		if (!is_string($this->limit_offset_clause)) {
			throw new Exception("limit clause must be a string");
		}
		foreach ($this->joins as $join) {
			if (!is_string($join) || trim($join) === "") {
				throw new Exception("join must be a string");
			}
		}
		if ($this->order_by_clause === null) {
			$this->order_by_clause = "";
		}
		if (!is_string($this->order_by_clause)) {
			throw new Exception("order clause must be a string");
		}
		foreach ($this->where_items as $where) {
			if (!is_string($where) || trim($where) === "") {
				throw new Exception("where must be a string");
			}
		}
		foreach ($this->group_by_columns as $group_column) {
			if (!is_string($group_column) || trim($group_column) === "") {
				throw new Exception("group_column must be a string");
			}
		}
		foreach ($this->select_items as $select) {
			if (count($select) !== 2) {
				throw new Exception("Each item in select_items must be an array of two items (clause, alias)");
			}
			$select_piece = $select[0];
			$alias = $select[1];
			if (!is_string($select_piece) || trim($select_piece) === "") {
				throw new Exception("select must be a string");
			}
			if ($alias !== null && !is_string($alias)) {
				throw new Exception("alias must be a string or null");
			}
		}
		if ($this->table === null) {
			$this->table = "";
		}
		if (!is_string($this->table)) {
			throw new Exception("table name must be a string");
		}
		if ($this->settings !== null && !($this->settings instanceof DataTableSettings)) {
			throw new Exception("settings must be instanceof DataTableSettings");
		}
		if ($this->state !== null && !($this->state instanceof DataFormState)) {
			throw new Exception("state must be instanceof DataFormState");
		}
		if ($this->is_distinct === null) {
			$this->is_distinct = false;
		}
		if (!is_bool($this->is_distinct)) {
			throw new Exception("is_distinct must be a bool");
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

	public function obtain_row_count() {
		$this->validate_input();

		$constructor = new SqlConstructor($this);

		if (!$this->ignore_filtering) {
			$where_clauses = FilterTreeTransform::make_where_clauses($this->state,
				$this->settings, $this->table, $this->alias_lookup);
			foreach ($where_clauses as $where_clause) {
				$constructor->add_where_item($where_clause);
			}
		}
		return "SELECT COUNT(*) FROM (" . $constructor->make_sql() . ") as f";
	}

	/**
	 * @return string
	 */
	public function build_count() {
		return $this->obtain_row_count();
	}

	/**
	 * Make SQL string from object contents
	 *
	 * @return string SQL
	 * @throws Exception
	 */
	public function obtain_paginated_data() {
		$this->validate_input();

		$constructor = new SqlConstructor($this);

		if (!$this->ignore_filtering) {
			$where_clauses = FilterTreeTransform::make_where_clauses($this->state,
				$this->settings, $this->table, $this->alias_lookup);
			foreach ($where_clauses as $where_clause) {
				$constructor->add_where_item($where_clause);
			}
		}
		$order_clause = SortTreeTransform::make_order_clause($this->state, $this->settings, $this->table);
		if ($order_clause) {
			$constructor->order_clause($order_clause);
		}
		if (!$this->ignore_pagination) {
			$limit_clause = LimitPaginationTreeTransform::make_limit_offset_clause($this->state, $this->settings, $this->table);
			if ($limit_clause) {
				$constructor->limit_offset_clause($limit_clause);
			}
		}
		return $constructor->make_sql();
	}

	/**
	 * @return string
	 */
	public function build() {
		return $this->obtain_paginated_data();
	}

	public function obtain_paginated_data_and_row_count() {
		return array($this->obtain_paginated_data(), $this->obtain_row_count());
	}

	/**
	 * Construct SQL without doing any filtering sorting or pagination
	 * @return string
	 */
	protected function make_sql() {
		$this->validate_input();
		$ret = "SELECT ";
		if ($this->is_distinct) {
			$ret .= " DISTINCT ";
		}
		$first_select = true;
		foreach ($this->select_items as $item) {
			$clause = $item[0];
			$alias = $item[1];

			if (!$first_select) {
				$ret .= ", ";
			}

			$ret .= " " . $clause . " ";
			if ($alias !== null) {
				$ret .= " AS " . $alias . " ";
			}


			$first_select = false;
		}
		$ret .= " FROM " . $this->from_table;
		foreach ($this->joins as $join) {
			$ret .= " " . $join . " ";
		}
		$first_where = true;
		foreach ($this->where_items as $where) {
			if ($first_where) {
				$ret .= " WHERE ";
			}
			else
			{
				$ret .= " AND ";
			}
			$ret .= " " . $where . " ";

			$first_where = false;
		}
		if ($this->group_by_columns) {
			$ret .= " GROUP BY ";
			foreach ($this->group_by_columns as $group_column) {
				$ret .= $group_column . " ";
			}
		}
		if ($this->order_by_clause) {
			$ret .= " ORDER BY " . $this->order_by_clause . " ";
		}
		$ret .= $this->limit_offset_clause;


		return $ret;
	}
}