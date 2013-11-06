<?php
require_once "data_form.php";
require_once FILE_BASE_PATH . "/lib/PHP-SQL-Parser/php-sql-creator.php";
require_once FILE_BASE_PATH . "/lib/PHP-SQL-Parser/php-sql-parser.php";

/**
 * Produce SQL given a DataFormState
 */
class SQLBuilder {
	/**
	 * @var array Tree posted PHP-SQL-Parser. Must only be modified by push_ methods and constructor
	 */
	protected $sql_tree;

	/** @var  DataFormState */
	protected $state;

	/** @var  string */
	protected $table_name;

	/** @var  DataTableSettings */
	protected $settings;

	/**
	 * @param $sql string
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

	public function settings($settings) {
		$this->settings = $settings;
		return $this;
	}

	/**
	 * @throws Exception
	 */
	protected function validate_inputs() {
		if (!$this->table_name) {
			$this->table_name = "";
		}
		if (!is_string($this->table_name)) {
			throw new Exception("table_name must be a string");
		}

		if ($this->state && !($this->state instanceof DataFormState)) {
			throw new Exception("state must be instance of DataFormState");
		}

		if ($this->settings &&
			!($this->settings instanceof DataTableSettings)) {
			throw new Exception("settings must be DataTableSettings");
		}

		if (!is_array($this->sql_tree)) {
			throw new Exception("parse() must be called before calling build() or build_count()");
		}
		if (!array_key_exists("SELECT", $this->sql_tree)) {
			throw new Exception("SQL statement must be a SELECT statement");
		}
		if (array_key_exists("LIMIT", $this->sql_tree) && $this->settings && $this->settings->uses_pagination()) {
			throw new Exception("The LIMIT clause is added automatically when sorting so it shouldn't be present in statement yet");
		}
	}
	/**
	 * @return string SQL which counts the rows
	 */
	public function build_count() {
		$this->validate_inputs();

		$tree = $this->create_sql(true);
		$creator = new PHPSQLCreator();
		return $creator->create($tree);
	}

	/**
	 * Create SQL from state and clauses
	 *
	 * @return string SQL (not escaped!)
	 * @throws Exception
	 */
	public function build() {
		$this->validate_inputs();

		$tree = $this->create_sql(false);
		$creator = new PHPSQLCreator();
		return $creator->create($tree);
	}

	/**
	 * Create ORDER BY portion of SQL
	 *
	 * @param $input_tree array SQL tree
	 * @param $state DataFormState
	 * @param $table_name string
	 * @return array Modified SQL tree
	 * @throws Exception
	 */
	protected static function create_orderby($input_tree, $state, $table_name) {
		$tree = $input_tree;

		if ($state) {
			if ($table_name) {
				$sorting_data = $state->find_item(array(DataFormState::state_key, $table_name, DataFormState::sorting_state_key));
			}
			else
			{
				$sorting_data = $state->find_item(array(DataFormState::state_key, DataFormState::sorting_state_key));
			}
			if (is_array($sorting_data)) {
				// remove any ORDER clause already present
				$tree["ORDER"] = array();

				foreach ($sorting_data as $column_key => $value) {
					if (is_string($value)) {
						if ($value == DataFormState::sorting_state_desc ||
							$value == DataFormState::sorting_state_asc) {
							// create new ORDER clause
							$tree["ORDER"][] = array("expr_type" => "colref",
													 "base_expr" => $column_key,
													 "subtree" => false,
													 "direction" => strtoupper($value));
						}
						elseif ($value)
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
		return $tree;
	}

	/**
	 * Create SQL. Assumes validation of inputs is done
	 *
	 * @param $count_only bool
	 * @return array SQL tree
	 */
	protected function create_sql($count_only) {
		$tree = $this->sql_tree;
		// note that this tree is an array which are copy-on-write in PHP
		// so the original will not be modified

		// replace SELECT arguments with COUNT(*)
		if ($count_only) {
			$count_parser = new PHPSQLParser();
			$select_count_all = $count_parser->parse("SELECT COUNT(*)");
			$tree["SELECT"] = $select_count_all["SELECT"];
		}

		$filtered_tree = self::create_filters($tree, $this->state, $this->table_name);

		if ($count_only) {
			return $filtered_tree;
		}
		else
		{
			$sorted_tree = self::create_orderby($filtered_tree, $this->state, $this->table_name);

			if ($this->settings && $this->settings->uses_pagination()) {
				$pagination_tree = self::create_pagination($sorted_tree, $this->settings, $this->state, $this->table_name);
				return $pagination_tree;
			}
			else
			{
				return $sorted_tree;
			}
		}
	}

	/**
	 * Add WHERE clauses for filters
	 * @param $input_tree array input tree
	 * @param $state DataFormState
	 * @param $table_name string
	 * @throws Exception
	 * @return array Modified tree
	 */
	protected static function create_filters($input_tree, $state, $table_name) {
		$tree = $input_tree;

		$subtrees_to_add = array();

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
						$escaped_value = str_replace("'", "''", $value);
						// TODO: escape $key, but I don't know if $key will contain table name too
						$phrase = " $key LIKE '%$escaped_value%' ";
						$parser = new PHPSQLParser();
						$subtrees_to_add[] = $parser->parse($phrase);

					}
					else
					{
						throw new Exception("sorting value should be a string");
					}
				}
			}
		}

		if ($subtrees_to_add) {
			if (!array_key_exists("WHERE", $tree)) {
				$tree["WHERE"] = array();
			}
			if ($tree["WHERE"]) {
				// add AND
				$tree["WHERE"][] = array("expr_type" => "operator",
										 "base_expr" => "and",
										 "sub_tree" => false);
			}
			foreach ($subtrees_to_add as $subtree) {
				$tree["WHERE"][] = $subtree;
			}
		}
		return $tree;
	}

	/**
	 * Add LIMIT and OFFSET clauses given pagination state and settings
	 *
	 * @param $input_tree array
	 * @param $settings DataTableSettings
	 * @param $state DataFormState
	 * @param $table_name string
	 * @return array SQL tree
	 */
	protected static function create_pagination($input_tree, $settings, $state, $table_name)
	{
		$tree = $input_tree;
		if ($state) {
			$pagination_state = $state->get_pagination_state($table_name);

			if (is_null($pagination_state->get_limit())) {
				$limit = $settings->get_default_limit();
			}
			else
			{
				$limit = $pagination_state->get_limit();
			}

			if (is_null($pagination_state->get_current_page())) {
				$current_page = 0;
			}
			else
			{
				$current_page = $pagination_state->get_current_page();
			}

			$offset = $current_page * $limit;
			$tree["LIMIT"] = array("offset" => $offset,
								   "rowcount" => $limit);
		}
		return $tree;
	}
}