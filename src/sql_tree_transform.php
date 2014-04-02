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
 * Transforms a SQL tree into a new tree
 */
interface ISQLTreeTransform {
	/**
	 * Apply the transform and return the result. Note that the original array is not
	 * altered since arrays are passed by value, nor would we want to modify it.
	 *
	 * @param $input_tree array A SQL tree object produced by PHP-SQL-Parser library
	 * @param $state DataFormState State from form
	 * @param $settings DataTableSettings Default settings for DataTable
	 * @param $table_name string Name of table, if any
	 * @return array The altered array.
	 */
	function alter($input_tree, $state, $settings, $table_name);
}

/**
 * Returns top-most clause with SELECT in SQL parse tree, or null if none found
 *
 * @param $tree array
 * @return array
 */
function &find_select_root_clause(&$tree) {
	$current =& $tree;

	if (!is_array($current)) {
		return null;
	}
	if (array_key_exists("SELECT", $tree)) {
		return $tree;
	}


	foreach ($tree as $k => &$v) {
		if (is_array($v)) {
			$ret =& find_select_root_clause($v);
			if ($ret !== null) {
				return $ret;
			}
		}
	}
	return null;
}
/**
 * Does no transformation, just returns input tree
 */
class IdentityTreeTransform implements ISQLTreeTransform {
	function alter($input_tree, $state, $settings, $table_name)
	{
		return $input_tree;
	}
}

/**
 * Paginate on a column value
 */
class WherePaginationTreeTransform implements ISQLTreeTransform {
	/**
	 * @var string
	 */
	protected $column_key;
	public function __construct($column_key) {
		if (!is_string($column_key) || trim($column_key) === "") {
			throw new Exception("column_key must be non-empty string");
		}
		$this->column_key = $column_key;
	}

	function alter($input_tree, $state, $settings, $table_name)
	{
		if ($state) {
			$pagination_state = $state->get_pagination_state($table_name);

			$limit = DataTableSettings::calculate_limit($settings, $pagination_state);
			if ($limit !== 0) {
				$current_page = DataTableSettings::calculate_current_page($settings, $pagination_state);

				$offset = $current_page * $limit;

				$alias_lookup = FilterTreeTransform::make_alias_lookup($input_tree);

				if (array_key_exists($this->column_key, $alias_lookup)) {
					$alias = $alias_lookup[$this->column_key];
				}
				else
				{
					$alias = $this->column_key;
				}

				// TODO: proper handling of quotes such that something like
				// `table`.`column` and `name with spaces` are handled correctly
				// and consistently
				if (strpos($alias, ".") === false) {
					$quoted_column_key = "`$alias`";
				}
				else
				{
					$quoted_column_key = $alias;
				}

				return FilterTreeTransform::add_where_clause($input_tree, "(" .
					$quoted_column_key . " >= " . $offset . " AND " . $quoted_column_key .
					" < " . ($offset + $limit) . ")");
			}
			// else the limit is zero and we don't paginate at all
		}
		return $input_tree;

	}
}

/**
 * Add LIMIT and OFFSET clauses given pagination state and settings
 */
class LimitPaginationTreeTransform implements ISQLTreeTransform
{
	/**
	 * @param $state DataFormState
	 * @param $settings DataTableSettings
	 * @param $table_name string
	 * @return string
	 */
	public static function make_limit_offset_clause($state, $settings, $table_name) {
		if ($state) {
			$pagination_state = $state->get_pagination_state($table_name);

			$limit = DataTableSettings::calculate_limit($settings, $pagination_state);
			if ($limit !== 0) {
				$current_page = DataTableSettings::calculate_current_page($settings, $pagination_state);

				$offset = $current_page * $limit;

				return "LIMIT $limit OFFSET $offset";
			}
			// else the limit is zero and we don't paginate at all
		}
		return "";
	}

	/**
	 * @param $tree array
	 * @param $limit_order_clause string
	 * @return array
	 */
	public static function add_limit_offset_clause($tree, $limit_order_clause) {
		$limit_parser = new PHPSQLParser();
		$limit_tree = $limit_parser->parse("SELECT a FROM x $limit_order_clause");
		$tree["LIMIT"] = $limit_tree["LIMIT"];
		return $tree;
	}

	function alter($input_tree, $state, $settings, $table_name)
	{
		$tree = $input_tree;
		$limit_clause = self::make_limit_offset_clause($state, $settings, $table_name);
		if ($limit_clause) {
			$tree = self::add_limit_offset_clause($input_tree, $limit_clause);
		}
		return $tree;
	}
}


/**
 * Put query into subquery and count all rows.
 */
class CountTreeTransform implements ISQLTreeTransform {
	function alter($input_tree, $state, $settings, $table_name)
	{
		$root = find_select_root_clause($input_tree);
		if ($root === null) {
			throw new Exception("Could not find SELECT clause");
		}

		$count_parser = new PHPSQLParser();
		$select_count_all = $count_parser->parse("SELECT COUNT(*) FROM (SELECT 3, 4, 5) as t");
		$select_count_all["FROM"][0]["sub_tree"] = $root;

		return $select_count_all;
	}
}

/**
 * Replace whole parse tree with a new one
 */
class ArbitraryTreeTransform implements ISQLTreeTransform {
	/**
	 * @var array
	 */
	protected $tree;
	public function __construct($new_sql) {
		if (!is_string($new_sql) || trim($new_sql) === "") {
			throw new Exception("new_sql must be a non-empty string");
		}
		$parser = new PHPSQLParser();
		$this->tree = $parser->parse($new_sql);
		if (!$this->tree) {
			throw new Exception("Unable to parse SQL");
		}
	}

	function alter($input_tree, $state, $settings, $table_name)
	{
		return $this->tree;
	}
}

/**
 * Create ORDER BY portion of SQL
 */
class SortTreeTransform  implements ISQLTreeTransform
{
	/**
	 * @param $tree array
	 * @param $clause string
	 * @return array
	 */
	public static function add_order_clause($tree, $clause) {
		if (trim($clause) === "") {
			unset($tree["ORDER"]);
			return $tree;
		}
		$order_parser = new PHPSQLParser();
		$order_tree = $order_parser->parse("SELECT z FROM x ORDER BY $clause");
		$tree["ORDER"] = $order_tree["ORDER"];
		return $tree;
	}

	/**
	 * @param $state DataFormState
	 * @param $settings DataTableSettings
	 * @param $table_name string
	 * @return string
	 * @throws Exception
	 */
	public static function make_order_clause($state, $settings, $table_name) {
		$ret = "";
		if ($state) {
			if (trim($table_name) !== "") {
				$sorting_data = $state->find_item(array(DataFormState::state_key, $table_name, DataFormState::sorting_state_key));
			}
			else
			{
				$sorting_data = $state->find_item(array(DataFormState::state_key, DataFormState::sorting_state_key));
			}
			if (is_array($sorting_data)) {
				foreach ($sorting_data as $column_key => $value) {
					if (is_string($value)) {
						if ($value == DataFormState::sorting_state_desc ||
							$value == DataFormState::sorting_state_asc) {
							// create new ORDER clause

							// TODO: proper handling of quotes such that something like
							// `table`.`column` and `name with spaces` are handled correctly
							// and consistently
							if (strpos($column_key, ".") === false) {
								$quoted_column_key = "`$column_key`";
							}
							else
							{
								$quoted_column_key = $column_key;
							}

							$ret .= " " . $quoted_column_key . " " . $value;
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
		return $ret;
	}

	function alter($input_tree, $state, $settings, $table_name)
	{
		$tree = $input_tree;

		$order_clause = self::make_order_clause($state, $settings, $table_name);
		if ($order_clause) {
			$tree = self::add_order_clause($tree, $order_clause);
		}

		return $tree;
	}
}

/**
 * Add WHERE clauses for filters
 */
class FilterTreeTransform  implements ISQLTreeTransform
{
	/**
	 * Make a map of column key aliases -> what they alias to. We need this because the WHERE clause can't use aliases,
	 * which are usually what we're using for column keys.
	 *
	 * @param $tree array
	 * @return array
	 */
	public static function make_alias_lookup($tree) {
		$lookup = array();

		$fake_clause = "SELECT a";
		$parser = new PHPSQLParser();
		$creator = new PHPSQLCreator();

		$fake_tree = $parser->parse($fake_clause);

		$root = find_select_root_clause($tree);
		if ($root !== null && array_key_exists("SELECT", $root)) {
			$select = $root["SELECT"];

			foreach ($select as $select_item) {
				if (array_key_exists("alias", $select_item)) {
					$alias = $select_item["alias"];
					if (is_array($alias) && array_key_exists("no_quotes", $alias)) {
						$no_quotes = $alias["no_quotes"];

						$fake_tree["SELECT"][0] = $select_item;
						$fake_tree["SELECT"][0]["delim"] = "";
						unset($fake_tree["SELECT"][0]["alias"]);
						$expr = $creator->create($fake_tree);

						if (substr($expr, 0, strlen("SELECT")) === "SELECT") {
							$expr = substr($expr, strlen("SELECT"));
						}
						$lookup[$no_quotes] = $expr;
					}
				}
			}
		}


		return $lookup;
	}

	/**
	 * Add WHERE clause to $tree.
	 *
	 * @param $input_tree array
	 * @param $clause string (Not escaped!)
	 * @return array SQL tree
	 * @throws Exception
	 */
	public static function add_where_clause($input_tree, $clause) {
		$phrase = "SELECT * FROM xyz WHERE $clause ";
		$parser = new PHPSQLParser();
		$where_clause = $parser->parse($phrase);
		if (!$where_clause) {
			throw new Exception("Error parsing SQL phrase");
		}

		$tree =& find_select_root_clause($input_tree);

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
		return $input_tree;
	}

	/**
	 * @param $state DataFormState
	 * @param $settings DataTableSettings
	 * @param $table_name string
	 * @param $alias_lookup array
	 * @return string[] List of WHERE clauses, joined with AND
	 * @throws Exception
	 */
	public static function make_where_clauses($state, $settings, $table_name, $alias_lookup) {
		$ret = array();

		// TODO: make this less ugly
		if ($state) {
			if ($table_name) {
				$searching_state = $state->find_item(array(DataFormState::state_key, $table_name, DataFormState::searching_state_key));
			}
			else
			{
				$searching_state = $state->find_item(array(DataFormState::state_key, DataFormState::searching_state_key));
			}
			if (is_array($searching_state)) {
				foreach (array_keys($searching_state) as $column_key) {
					$obj = $state->get_searching_state($column_key, $table_name);

					if (array_key_exists($column_key, $alias_lookup)) {
						$column_base_expr = $alias_lookup[$column_key];
					}
					else
					{
						$column_base_expr = $column_key;
					}

					if ($obj) {
						$params = $obj->get_params();
						$type = $obj->get_type();
						if ($type === DataTableSearchState::like ||
							$type === DataTableSearchState::rlike ||
							$type === DataTableSearchState::less_than ||
							$type === DataTableSearchState::less_or_equal ||
							$type === DataTableSearchState::greater_than ||
							$type === DataTableSearchState::greater_or_equal ||
							$type === DataTableSearchState::equal ||
							$type === DataTableSearchState::in) {
							$escaped_value = gfy_db::escape_string($params[0]);
							// TODO: check is_numeric for numeric comparisons
							if ($escaped_value !== "") {
								if ($type === DataTableSearchState::like) {
									$like_escaped_value = escape_like_parameter($escaped_value);
									$phrase = " $column_base_expr LIKE '%$like_escaped_value%' ESCAPE '\\\\' ";
								}
								elseif ($type === DataTableSearchState::rlike) {
									$phrase = " $column_base_expr RLIKE '$escaped_value' ";
								}
								elseif ($type === DataTableSearchState::less_than) {
									$phrase = " CAST($column_base_expr AS DECIMAL(15,15)) < $escaped_value ";
								}
								elseif ($type === DataTableSearchState::less_or_equal) {
									$phrase = " CAST($column_base_expr AS DECIMAL(15,15))  <= $escaped_value ";
								}
								elseif ($type === DataTableSearchState::greater_than) {
									$phrase = " CAST($column_base_expr AS DECIMAL(15,15))  > $escaped_value ";
								}
								elseif ($type === DataTableSearchState::greater_or_equal) {
									$phrase = " CAST($column_base_expr AS DECIMAL(15,15))  >= $escaped_value ";
								}
								elseif ($type === DataTableSearchState::equal) {
									if (is_numeric($escaped_value)) {
										$phrase = " $column_base_expr = $escaped_value ";
									}
									else
									{
										$phrase = " $column_base_expr = '$escaped_value' ";
									}
								}
								elseif ($type === DataTableSearchState::in) {
									$phrase = " $column_base_expr IN ($escaped_value) ";
								}
								else {
									throw new Exception("Unimplemented for search type " . $type);
								}

								$ret[] = $phrase;
							}
						}
					}
				}
			}
		}

		return $ret;
	}

	function alter($input_tree, $state, $settings, $table_name)
	{
		$tree = $input_tree;

		$alias_lookup = self::make_alias_lookup($tree);

		$where_clauses = self::make_where_clauses($state, $settings, $table_name, $alias_lookup);

		foreach ($where_clauses as $where_clause) {
			$tree = self::add_where_clause($tree, $where_clause);
		}

		return $tree;
	}
}
