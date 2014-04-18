<?php
/**
 * Example showing pagination in DataForms
 *
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";

/**
 * Simple formatter which highlights prime numbers in red
 */
class PrimeFormatter implements IDataTableCellFormatter {
	/**
	 * Note that we are only doing this on whatever number of rows on the page
	 * @param $x int
	 * @return bool
	 */
	private static function is_prime($x) {
		if ($x < 2) {
			return false;
		}
		else {
			$sqrt_x = sqrt($x);
			for ($i = 2; $i < $sqrt_x + 1; $i++) {
				if ($x % $i == 0) {
					return false;
				}
			}
		}
		return true;
	}

	public function format($form_name, $column_header, $column_data, $rowid, $state)
	{
		if (self::is_prime($column_data)) {
			return "<span style='color: red;'>$column_data</span>";
		}
		else
		{
			return $column_data;
		}
	}
}

/**
 * This demonstrates pagination if we did it manually based on form state. Usually
 * you would use paginate_sql() or paginate_array() to do this automatically instead.
 *
 * @param DataFormState $state Contains pagination information
 * @return DataForm
 */
function make_form($state) {
	$this_url = "pagination.php";

	// just one column here, a list of numbers, highlighted with PrimeFormatter if prime, and sortable
	$columns = array();
	$columns[] = DataTableColumnBuilder::create()
		->display_header_name("Prime numbers")
		->column_key("number")
		->cell_formatter(new PrimeFormatter())
		->sortable(true)
		->build();

	// Paginate through numbers 0 through 1472
	$total_count = 1473;
	// Make pagination (and other DataTable) settings
	// Note that total_rows needs to be set here so that the DataTable
	// can calculate how many pages there are.
	$settings = DataTableSettingsBuilder::create()
		->total_rows($total_count)
		->default_limit(25)
		->build();

	// If we have more than one table in a form, we would want to specify a name here
	// so the form can tell them apart
	$table_name = "";
	// PaginationInfo looks at default values from $settings
	// and user supplied values from $state and figures out the proper values
	// for pagination, filtering and sorting values
	$pagination_info = DataFormState::make_pagination_info($state, $settings, $table_name);
	$current_page = $pagination_info->calculate_current_page($total_count);

	$limit = $pagination_info->get_limit();
	if ($limit !== 0) {
		// fill in the data within the page boundaries
		$start = $limit * $current_page;
		$end = $limit * ($current_page + 1);
		if ($end > $total_count) {
			$end = $total_count;
		}
	}
	else
	{
		// A limit of 0 means display all rows
		$start = 0;
		$end = $total_count;
	}

	$sorting_state = $pagination_info->get_sorting_states();

	// now that we have start and end numbers, create some data. Make it sorted if necessary
	$rows = array();
	for ($i = $start; $i < $end; $i++) {
		if (array_key_exists("number", $sorting_state)) {
			/** @var DataTableSortingState $column_sorting_state */
			$column_sorting_state = $sorting_state["number"];
			if ($column_sorting_state->get_direction() === DataTableSortingState::sort_order_desc) {
				$rows[] = array("number" => $total_count - $i - 1);
			}
			else
			{
				$rows[] = array("number" => $i);
			}
		}
		else
		{
			$rows[] = array("number" => $i);
		}
	}

	// create the DataTable and DataForm
	$table = DataTableBuilder::create()
		->columns($columns)
		->rows($rows)
		->settings($settings)
		->build();
	$form = DataFormBuilder::create($state->get_form_name())
		->tables(array($table))
		->remote($this_url)
		->build();
	return $form;
}

try {
	$state = new DataFormState("primes", $_GET);
	if ($state->only_display_form()) {
		try
		{
			$form = make_form($state);
			echo $form->display_form($state);
		}
		catch (Exception $e) {
			echo json_encode(array("error" => $e->getMessage()));
		}
	}
	else
	{
		$form = make_form($state);
		gfy_header("Pagination example", "");
		echo $form->display($state);
	}
}
catch (Exception $e) {
	echo "<pre>" . $e->getMessage() . "</pre>";
}