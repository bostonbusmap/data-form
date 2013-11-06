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
class CountTreeTransform  implements ISQLTreeTransform
{
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
}