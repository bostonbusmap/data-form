<?php

require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";

class PrimeFormatter implements IDataTableCellFormatter {
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
 * @param DataFormState $state
 * @return DataForm
 */
function make_form($state) {
	$this_url = HTTP_BASE_PATH . "/browser/lib/data_table/examples/pagination.php";

	$pagination = $state->get_pagination_state();
	$current_page = $pagination->get_current_page();

	$columns = array();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Prime numbers")->column_key("number")->
		cell_formatter(new PrimeFormatter())->sortable(true)->build();

	$rows = array();
	$total_count = 1473;
	$pagination_settings = new DataTablePaginationSettings(25, $total_count);

	$limit = $pagination->get_limit();
	if (is_null($limit)) {
		$limit = $pagination_settings->get_default_limit();
	}
	elseif ($limit === 0) {
		$limit = $total_count;
	}

	$start = $limit * $current_page;
	$end = $limit * ($current_page + 1);
	for ($i = $start; $i < $end; $i++) {
		if ($state->get_sorting_state("number") == DataFormState::sorting_state_desc) {
			$rows[] = array("number" => $total_count - $i - 1);
		}
		else
		{
			$rows[] = array("number" => $i);
		}
	}


	$table = DataTableBuilder::create()->columns($columns)->rows($rows)->remote($this_url)->
		pagination_settings($pagination_settings)->build();
	$form = DataFormBuilder::create("primes")->tables(array($table))->build();
	return $form;
}

try {
	$state = new DataFormState("primes", $_POST);
	$form = make_form($state);
	if ($state->only_display_form()) {
		echo $form->display_form($state);
	}
	else
	{
		gfy_header("Simple table example", "");
		echo $form->display($state);
	}
}
catch (Exception $e) {
	echo "<pre>" . $e . "</pre>";
}