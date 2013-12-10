<?php

require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";

function compare_bird_column_asc($a, $b) {
	return $a["bird"] < $b["bird"];
}

function compare_bird_column_desc($a, $b) {
	return $a["bird"] > $b["bird"];
}

/**
 * @param DataFormState $state
 * @return DataForm
 */
function make_form($state) {
	$this_url = HTTP_BASE_PATH . "/browser/lib/data_table/examples/searchable.php";

	$columns = array();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Bird")->column_key("bird")->
		searchable(true)->sortable(true)->build();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Bird weight")->column_key("weight")->
		searchable(true)->search_formatter(new NumericalSearchFormatter())->build();

	$birds = array("1.1 Struthioniformes",
		"1.2 Anseriformes",
		"1.3 Galliformes",
		"1.4 Charadriiformes",
		"1.5 Gruiformes",
		"1.6 Podicipediformes",
		"1.7 Ciconiiformes",
		"1.8 Pelecaniformes",
		"1.9 Procellariiformes",
		"1.10 Sphenisciformes",
		"1.11 Columbiformes",
		"1.12 Psittaciformes",
		"1.13 Cuculiformes",
		"1.14 Falconiformes",
		"1.15 Strigiformes",
		"1.16 Caprimulgiformes",
		"1.17 Apodiformes",
		"1.18 Coraciiformes",
		"1.19 Piciformes",
		"1.20 Passeriformes");

	$bird_weights = array();
	mt_srand(0);
	foreach ($birds as $bird) {
		$bird_weights[$bird] = floor((((mt_rand() / mt_getrandmax()) * 70) + 10) * 4) / 4;
	}

	$rows = array();
	foreach ($birds as $bird) {
		$rows[] = array("bird" => $bird, "weight" => $bird_weights[$bird]);
	}

	// in PHP 5.3 we can replace this with array_filter($rows, function($row) { return($row["weight"] < $value });
	$indexes_to_remove = array();
	$bird_search = $state->get_searching_state("bird");
	if ($bird_search) {
		if ($bird_search->get_type() === DataTableSearchState::like) {
			$params = $bird_search->get_params();
			$value = $params[0];
			if (trim($value) !== "") {

				foreach ($rows as $i => $row) {
					if (strpos(strtolower($row["bird"]), strtolower($value)) === false) {
						$indexes_to_remove[] = $i;
					}
				}
			}
		}
		else
		{
			throw new Exception("Unhandled search type");
		}
	}

	$weight_search = $state->get_searching_state("weight");
	if ($weight_search) {
		$params = $weight_search->get_params();
		$type = $weight_search->get_type();
		if ($type === DataTableSearchState::less_or_equal ||
			$type === DataTableSearchState::less_than ||
			$type === DataTableSearchState::greater_or_equal ||
			$type === DataTableSearchState::greater_than ||
			$type === DataTableSearchState::equal) {
			$value_string = $params[0];
			if (is_numeric($value_string)) {
				$value = (float)$value_string;
				foreach ($rows as $i => $row) {
					if ($type === DataTableSearchState::less_or_equal && $row["weight"] > $value) {
						$indexes_to_remove[] = $i;
					}
					if ($type === DataTableSearchState::less_than && $row["weight"] >= $value) {
						$indexes_to_remove[] = $i;
					}
					if ($type === DataTableSearchState::greater_or_equal && $row["weight"] < $value) {
						$indexes_to_remove[] = $i;
					}
					if ($type === DataTableSearchState::greater_than && $row["weight"] <= $value) {
						$indexes_to_remove[] = $i;
					}
					if ($type === DataTableSearchState::equal && $row["weight"] !== $value) {
						$indexes_to_remove[] = $i;
					}
				}
			}
		}
	}

	foreach ($indexes_to_remove as $i) {
		unset($rows[$i]);
	}

	if ($state->get_sorting_state("bird") == DataFormState::sorting_state_asc) {
		usort($rows, "compare_bird_column_asc");
	}
	elseif ($state->get_sorting_state("bird") == DataFormState::sorting_state_desc) {
		usort($rows, "compare_bird_column_desc");
	}

	$buttons = array();
	$buttons[] = DataTableButtonBuilder::create()->text("Refresh")->name("refresh")->form_action($this_url)->
		behavior(new DataTableBehaviorRefresh())->build();

	$table = DataTableBuilder::create()->columns($columns)->rows($rows)->widgets($buttons)->build();
	$form = DataFormBuilder::create("searchable")->tables(array($table))->remote($this_url)->build();
	return $form;
}

try {
	$state = new DataFormState("searchable", $_POST);
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