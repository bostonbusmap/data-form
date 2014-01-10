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
 * @param DataFormState $state Contains pagination information
 * @return DataForm
 */
function make_form($state) {
	$this_url = "pagination.php";

	// The pagination state stores things like the current limit and page number
	$pagination_state = $state->get_pagination_state();

	// just one column here, a list of numbers, highlighted with PrimeFormatter if prime, and sortable
	$columns = array();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Prime numbers")->column_key("number")->
		cell_formatter(new PrimeFormatter())->sortable(true)->build();

	// Paginate through numbers 0 through 1472
	$total_count = 1473;
	// Make pagination (and other DataTable) settings
	// Note that total_rows needs to be set here so that the DataTable
	// can calculate how many pages there are.
	$settings = DataTableSettingsBuilder::create()->total_rows($total_count)->default_limit(25)->build();
	$current_page = DataTableSettings::calculate_current_page($settings, $pagination_state);

	// A limit of 0 means display all rows
	$limit = DataTableSettings::calculate_limit($settings, $pagination_state);

	// fill in the data within the page boundaries
	$start = $limit * $current_page;
	$end = $limit * ($current_page + 1);
	if ($end > $total_count) {
		$end = $total_count;
	}
	$rows = array();
	for ($i = $start; $i < $end; $i++) {
		if ($state->get_sorting_state("number") == DataFormState::sorting_state_desc) {
			$rows[] = array("number" => $total_count - $i - 1);
		}
		else
		{
			$rows[] = array("number" => $i);
		}
	}

	// create the DataTable and DataForm
	$table = DataTableBuilder::create()->columns($columns)->rows($rows)->
		settings($settings)->build();
	$form = DataFormBuilder::create($state->get_form_name())->tables(array($table))->remote($this_url)->build();
	return $form;
}

try {
	$state = new DataFormState("primes", $_GET);
	$form = make_form($state);
	if ($state->only_display_form()) {
		echo $form->display_form($state);
	}
	else
	{
		gfy_header("Pagination example", "");
		echo $form->display($state);
	}
}
catch (Exception $e) {
	echo "<pre>" . $e . "</pre>";
}