<?php
/**
 * Transforms a SQL tree into a new tree somehow
 */
interface ISQLTreeTransform {
	/**
	 * Apply the transform and return the result. Note that the original array is not
	 * altered since arrays are passed by value, nor would we want to modify it.
	 *
	 * @param $input_tree array A SQL tree object produced by PHP-SQL-Parser library
	 * @param $state DataFormState
	 * @param $settings DataTableSettings
	 * @param $table_name string
	 * @return array The altered array.
	 */
	function alter($input_tree, $state, $settings, $table_name);
}

/**
 * Add LIMIT and OFFSET clauses given pagination state and settings
 */
class LimitPaginationTreeTransform implements ISQLTreeTransform
{
	function alter($input_tree, $state, $settings, $table_name)
	{
		$tree = $input_tree;
		if ($state) {
			$pagination_state = $state->get_pagination_state($table_name);

			if (is_null($pagination_state->get_limit())) {
				if ($settings) {
					$limit = $settings->get_default_limit();
				}
				else
				{
					$limit = DataTableSettings::default_limit;
				}
			}
			elseif ($pagination_state->get_limit() == 0) {
				// all rows
				if ($settings && is_int($settings->get_total_rows())) {
					$limit = $settings->get_total_rows();
				}
				else
				{
					$limit = DataTableSettings::default_limit;
				}
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
class BoundedPaginationTreeTransform implements ISQLTreeTransform
{
	/** @var  string */
	protected $column_key;

	public function __construct($column_key) {
		if (!is_string($column_key) || trim($column_key) === "") {
			throw new Exception("expected column_key to be a string");
		}
		$this->column_key = $column_key;
	}

	function alter($input_tree, $state, $settings, $table_name)
	{
		$tree = $input_tree;


		if ($state) {
			$pagination_state = $state->get_pagination_state($table_name);

			if (is_null($pagination_state->get_limit())) {
				if ($settings) {
					$limit = $settings->get_default_limit();
				}
				else
				{
					$limit = DataTableSettings::default_limit;
				}
			}
			elseif ($pagination_state->get_limit() == 0) {
				// all rows
				if ($settings && is_int($settings->get_total_rows())) {
					$limit = $settings->get_total_rows();
				}
				else
				{
					$limit = DataTableSettings::default_limit;
				}
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

			$start = $limit * $current_page;
			$end = $limit * ($current_page + 1);

			$tree = self::add_where_clause($tree, $this->column_key . " >= " . $start);
			$tree = self::add_where_clause($tree, $this->column_key . " < " . $end);
		}

		return $tree;
	}

	/**
	 * @param $tree array
	 * @param $clause string (Not escaped!)
	 * @return array SQL tree
	 * @throws Exception
	 */
	public static function add_where_clause($tree, $clause) {
		$phrase = "SELECT * FROM xyz WHERE $clause ";
		$parser = new PHPSQLParser();
		$where_clause = $parser->parse($phrase);
		if (!$where_clause) {
			throw new Exception("Error parsing SQL phrase");
		}

		if (!array_key_exists("WHERE", $tree)) {
			$tree["WHERE"] = array();
		}
		if (!array_key_exists("WHERE", $where_clause)) {
			throw new Exception("SQL clause is lacking WHERE piece");
		}
		if ($tree["WHERE"]) {
			// add AND
			$tree["WHERE"][] = array("expr_type" => "operator",
				"base_expr" => "and",
				"sub_tree" => false);
		}
		foreach ($where_clause["WHERE"] as $where_piece) {
			$tree["WHERE"][] = $where_piece;
		}
		return $tree;
	}
}
class DistinctCountTreeTransform  implements ISQLTreeTransform
{
	/** @var  string */
	protected $column_key;

	public function __construct($column_key) {
		if (!is_string($column_key) || trim($column_key) === "") {
			throw new Exception("expected column_key to be a string");
		}
		if (strpos($column_key, "`") !== false) {
			throw new Exception("Found backtick in column key");
		}
		$this->column_key = $column_key;
	}

	function alter($input_tree, $state, $settings, $table_name)
	{
		$tree = $input_tree;

		$count_parser = new PHPSQLParser();
		$select_count_all = $count_parser->parse("SELECT COUNT(DISTINCT `" . $this->column_key . "`)");
		$tree["SELECT"] = $select_count_all["SELECT"];

		return $tree;
	}
}
class CountTreeTransform implements ISQLTreeTransform {
	function alter($input_tree, $state, $settings, $table_name)
	{
		$tree = $input_tree;

		$count_parser = new PHPSQLParser();
		$select_count_all = $count_parser->parse("SELECT COUNT(*)");
		$tree["SELECT"] = $select_count_all["SELECT"];

		return $tree;
	}
}
/**
 * Create ORDER BY portion of SQL
 */
class SortTreeTransform  implements ISQLTreeTransform
{
	function alter($input_tree, $state, $settings, $table_name)
	{
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
}

/**
 * Add WHERE clauses for filters
 */
class FilterTreeTransform  implements ISQLTreeTransform
{
	function alter($input_tree, $state, $settings, $table_name)
	{
		$tree = $input_tree;


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

						$tree = BoundedPaginationTreeTransform::add_where_clause($tree, $phrase);
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
}