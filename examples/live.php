<?php

require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";

class RedFormatter implements IDataTableCellFormatter {
	public function format($form_name, $column_header, $column_data, $rowid, $state)
	{
		return "<span style='color: red;'>$column_data</span>";
	}
}

function compare_result_column_desc($a, $b) {
	return $a["result"] < $b["result"];
}

function compare_result_column_asc($a, $b) {
	return $a["result"] > $b["result"];
}

/**
 * @param DataFormState $state
 * @return DataForm
 */
function make_form($state) {
	$this_url = HTTP_BASE_PATH . "/browser/lib/data_table/examples/live.php";

	$columns = array();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Numbers")->column_key("number")->build();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Result")->column_key("result")->sortable(true)->build();

	$multiplier = $state->find_item(array("multiplier"));
	if (is_null($multiplier)) {
		$multiplier = 4;
	}
	else
	{
		$multiplier = (int)$multiplier;
	}

	$rows = array();
	for ($i = 0; $i < 15; $i++) {
		$row = array();
		$row["number"] = $i;
		$row["result"] = ($i * $multiplier) % 7;

		$rows[] = $row;
	}

	if ($state->get_sorting_state("result") == DataFormState::sorting_state_asc) {
		usort($rows, "compare_result_column_asc");
	}
	elseif ($state->get_sorting_state("result") == DataFormState::sorting_state_desc) {
		usort($rows, "compare_result_column_desc");
	}

	$buttons = array();
	$buttons[] = new DataTableButton("(x * ?) % 7", "refresh", $this_url,
		new DataTableBehaviorRefresh());

	// note that '4' is selected by default, but this will be overridden
	// if the form is refreshed
	$options = array();
	$options[] = new DataTableOption("3", "3");
	$options[] = new DataTableOption("4", "4", true);
	$options[] = new DataTableOption("5", "5");


	$buttons[] = new DataTableOptions($options, "multiplier", $this_url, new DataTableBehaviorRefresh());

	$table = DataTableBuilder::create()->columns($columns)->rows($rows)->widgets($buttons)->remote($this_url)->build();
	$form = DataFormBuilder::create("multiplication")->tables(array($table))->build();
	return $form;
}

try {
	$state = new DataFormState("multiplication", $_POST);
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