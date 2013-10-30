<?php
require_once "data_form.php";

/**
 * Produce SQL given a DataFormState
 */
class SQLBuilder {
	/** @var  string[] */
	protected $wheres;
	/** @var  string[] */
	protected $selects;
	/** @var  string[] */
	protected $froms;

	/** @var  DataFormState */
	protected $state;

	/** @var  string */
	protected $table_name;

	public function __construct() {
		$this->wheres = array();
		$this->selects = array();
		$this->froms = array();
		$this->table_name = "";
	}

	/**
	 * @param $where string
	 * @return SQLBuilder
	 * @throws Exception
	 */
	public function push_where($where) {
		if (!is_string($where) || !$where) {
			throw new Exception("where must be a non-empty string");
		}
		$this->wheres[] = $where;
		return $this;
	}

	/**
	 * @param $select string
	 * @return SQLBuilder
	 * @throws Exception
	 */
	public function push_select($select) {
		if (!is_string($select) || !$select) {
			throw new Exception("select must be a non-empty string");
		}
		$this->selects[] = $select;
		return $this;
	}

	/**
	 * @param $from string
	 * @return SQLBuilder
	 * @throws Exception
	 */
	public function push_from($from) {
		if (!is_string($from) || !$from) {
			throw new Exception("from must be a non-empty string");
		}
		$this->froms[] = $from;
		return $this;
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
	 * @throws Exception
	 */
	protected function validate_inputs() {
		if ($this->state && !($this->state instanceof DataFormState)) {
			throw new Exception("state must be instance of DataFormState");
		}

		if (!$this->selects) {
			throw new Exception("At least one item in SELECT clause must be pushed");
		}
		if (!$this->froms) {
			throw new Exception("At least one item in FROM clause must be pushed");
		}
	}

	/**
	 * @return string SQL which counts the rows
	 */
	public function build_count() {
		$this->validate_inputs();

		return $this->create_sql(null, $this->state, $this->table_name,
			array("COUNT(*)"), $this->froms, $this->wheres);
	}

	/**
	 * Create SQL from state and clauses
	 *
	 * @param $pagination_settings DataTablePaginationSettings
	 * @return string SQL (not escaped!)
	 * @throws Exception
	 */
	public function build($pagination_settings) {
		$this->validate_inputs();

		return self::create_sql($pagination_settings, $this->state, $this->table_name,
			$this->selects, $this->froms, $this->wheres);
	}

	/**
	 * Create ORDER BY portion of SQL
	 *
	 * @param $state DataFormState
	 * @param $table_name string
	 * @return string[]
	 * @throws Exception
	 */
	protected static function create_orderby($state, $table_name) {
		$orderbys = array();
		if ($state) {
			if ($table_name) {
				$sorting_data = $state->find_item(array(DataFormState::state_key, $table_name, DataFormState::sorting_state_key));
			}
			else
			{
				$sorting_data = $state->find_item(array(DataFormState::state_key, DataFormState::sorting_state_key));
			}
			if (is_array($sorting_data)) {
				foreach ($sorting_data as $key => $value) {
					if (is_string($value)) {
						if ($value == DataFormState::sorting_state_desc ||
							$value == DataFormState::sorting_state_asc) {
							$orderbys[] = "ORDER BY $key $value ";
						}
						else
						{
							throw new Exception("Unexpected sorting value received: '$value'");
						}
					}
					else
					{
						throw new Exception("sorting value should be a string");
					}
				}
			}
		}
		return $orderbys;
	}

	/**
	 * Create SQL. Assumes validation of inputs is done
	 *
	 * @param $pagination_settings DataTablePaginationSettings
	 * @param $state DataFormState
	 * @param $table_name string
	 * @param $selects string[]
	 * @param $froms string[]
	 * @param $wheres string[]
	 * @return string
	 */
	protected static function create_sql($pagination_settings, $state, $table_name, $selects, $froms, $wheres) {
		$ret = "SELECT " . join(", ", $selects);
		$ret .= " FROM " . join(", ", $froms);

		$filter_wheres = self::create_filters($state, $table_name);
		$wheres = array_merge($wheres, $filter_wheres);
		if ($wheres) {
			$ret .= " WHERE (" . join(") AND (", $wheres) . ") ";
		}

		$orderbys = self::create_orderby($state, $table_name);

		$ret .= " " . join(" ", $orderbys);

		if ($pagination_settings) {
			$ret .= self::create_pagination($pagination_settings, $state, $table_name);
		}
		return $ret;
	}

	/**
	 * Add WHERE clauses for filters
	 * @param $state DataFormState
	 * @param $table_name string
	 * @throws Exception
	 * @return string[]
	 */
	protected static function create_filters($state, $table_name) {
		$ret = array();

		if ($state) {
			if ($table_name) {
				$searching_state = $state->find_item(array(DataFormState::state_key, $table_name, DataFormState::searching_state_key));
			}
			else
			{
				$searching_state = $state->find_item(array(DataFormState::state_key, DataFormState::searching_state_key));
			}
			if (is_array($searching_state)) {
				foreach ($searching_state as $key => $value) {
					if (is_string($value)) {
						$ret[] = " $key LIKE '%$value%' ";
					}
					else
					{
						throw new Exception("sorting value should be a string");
					}
				}
			}
		}

		return $ret;
	}

	/**
	 * Add LIMIT and OFFSET clauses given pagination state and settings
	 *
	 * @param $pagination_settings DataTablePaginationSettings
	 * @param $state DataFormState
	 * @param $table_name string
	 * @return string
	 */
	protected static function create_pagination($pagination_settings, $state, $table_name)
	{
		$ret = "";
		if ($state) {
			$pagination_state = $state->get_pagination_state($table_name);
			$offset = $pagination_state->get_current_page() * $pagination_state->get_limit();

			if (is_null($pagination_state->get_limit())) {
				$limit = $pagination_settings->get_default_limit();
			}
			else
			{
				$limit = $pagination_state->get_limit();
			}
			$ret .= " LIMIT $limit OFFSET $offset ";
		}
		return $ret;
	}
}