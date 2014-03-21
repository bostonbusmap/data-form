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
 * Add LIMIT and OFFSET clauses given pagination state and settings
 */
class LimitPaginationTreeTransform implements ISQLTreeTransform
{
	function alter($input_tree, $state, $settings, $table_name)
	{
		$tree = $input_tree;
		if ($state) {
			$pagination_state = $state->get_pagination_state($table_name);

			$limit = DataTableSettings::calculate_limit($settings, $pagination_state);
			if ($limit !== 0) {
				$current_page = DataTableSettings::calculate_current_page($settings, $pagination_state);

				$offset = $current_page * $limit;
				$tree["LIMIT"] = array("offset" => $offset,
					"rowcount" => $limit);
			}
			// else the limit is zero and we don't paginate at all
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

							$tree["ORDER"][] = array("expr_type" => "colref",
								"base_expr" => $quoted_column_key,
								"no_quotes" => $column_key,
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
	/**
	 * Make a map of column key aliases -> what they alias to. We need this because the WHERE clause can't use aliases,
	 * which are usually what we're using for column keys.
	 *
	 * @param $tree array
	 * @return array
	 */
	private static function make_alias_lookup($tree) {
		$lookup = array();

		$root = find_select_root_clause($tree);
		if ($root !== null && array_key_exists("SELECT", $root)) {
			$select = $root["SELECT"];
			foreach ($select as $select_item) {
				if (array_key_exists("base_expr", $select_item)) {
					$base_expr = $select_item["base_expr"];
					if (array_key_exists("alias", $select_item)) {
						$alias = $select_item["alias"];
						if (is_array($alias) && array_key_exists("no_quotes", $alias)) {
							$no_quotes = $alias["no_quotes"];
							$lookup[$no_quotes] = $base_expr;
						}
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
	private static function add_where_clause($input_tree, $clause) {
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
						if ($obj->get_type() === DataTableSearchState::like ||
							$obj->get_type() === DataTableSearchState::rlike ||
							$obj->get_type() === DataTableSearchState::less_than ||
							$obj->get_type() === DataTableSearchState::less_or_equal ||
							$obj->get_type() === DataTableSearchState::greater_than ||
							$obj->get_type() === DataTableSearchState::greater_or_equal ||
							$obj->get_type() === DataTableSearchState::equal) {
							$escaped_value = gfy_db::escape_string($params[0]);
							// TODO: check is_numeric for numeric comparisons
							if ($escaped_value !== "") {
								if ($obj->get_type() === DataTableSearchState::like) {
									$like_escaped_value = escape_like_parameter($escaped_value);
									$phrase = " $column_base_expr LIKE '%$like_escaped_value%' ESCAPE '\\\\' ";
								}
								elseif ($obj->get_type() === DataTableSearchState::rlike) {
									$phrase = " $column_base_expr RLIKE '$escaped_value' ";
								}
								elseif ($obj->get_type() === DataTableSearchState::less_than) {
									$phrase = " $column_base_expr < $escaped_value ";
								}
								elseif ($obj->get_type() === DataTableSearchState::less_or_equal) {
									$phrase = " $column_base_expr <= $escaped_value ";
								}
								elseif ($obj->get_type() === DataTableSearchState::greater_than) {
									$phrase = " $column_base_expr > $escaped_value ";
								}
								elseif ($obj->get_type() === DataTableSearchState::greater_or_equal) {
									$phrase = " $column_base_expr >= $escaped_value ";
								}
								elseif ($obj->get_type() === DataTableSearchState::equal) {
									if (is_numeric($escaped_value)) {
										$phrase = " $column_base_expr = $escaped_value ";
									}
									else
									{
										$phrase = " $column_base_expr = '$escaped_value' ";
									}
								}
								else {
									throw new Exception("Unimplemented for search type " . $obj->get_type());
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

		$alias_lookup = $this->make_alias_lookup($tree);

		$where_clauses = self::make_where_clauses($state, $settings, $table_name, $alias_lookup);

		foreach ($where_clauses as $where_clause) {
			$tree = self::add_where_clause($tree, $where_clause);
		}

		return $tree;
	}
}
