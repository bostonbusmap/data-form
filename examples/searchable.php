<?php
/**
 * Search DataForm example
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

// comparator for bird names
function compare_bird_column_asc($a, $b) {
	return $a["bird"] < $b["bird"];
}

function compare_bird_column_desc($a, $b) {
	return $a["bird"] > $b["bird"];
}

/**
 * Returns DataForm for bird vs bird weights
 *
 * @param DataFormState $state
 * @throws Exception
 * @return DataForm
 */
function make_form($state) {
	$this_url = HTTP_BASE_PATH . "/browser/lib/data_table/examples/searchable.php";

	// Two columns: bird name and some randomly generated bird weight
	// Note that the second column uses NumericalSearchFormatter to display a different search control
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

	// make up some semi-reasonable bird weights for each bird
	$bird_weights = array();
	// make this deterministic
	mt_srand(0);
	foreach ($birds as $bird) {
		$bird_weights[$bird] = floor((((mt_rand() / mt_getrandmax()) * 70) + 10) * 4) / 4;
	}

	// make our rows
	$rows = array();
	foreach ($birds as $bird) {
		$rows[] = array("bird" => $bird, "weight" => $bird_weights[$bird]);
	}

	// Don't allow pagination since this example isn't showing it off
	$settings = DataTableSettingsBuilder::create()
		->no_pagination()
		->build();

	$paginated_array = paginate_array($rows, $state, $settings);

	// Add refresh button. This isn't strictly necessary since you can press Enter to active search
	$buttons = array();
	$buttons[] = DataTableButtonBuilder::create()
		->text("Refresh")
		->form_action($this_url)
		->behavior(new DataTableBehaviorRefresh())
		->build();

	// Create DataTable and DataForm
	$table = DataTableBuilder::create()
		->columns($columns)
		->rows($paginated_array)
		->widgets($buttons)
		->settings($settings)
		->build();
	$form = DataFormBuilder::create("searchable")
		->tables(array($table))
		->remote($this_url)
		->build();
	return $form;
}

try {
	$state = new DataFormState("searchable", $_GET);
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
		gfy_header("Simple table example", "");
		echo $form->display($state);
	}
}
catch (Exception $e) {
	echo "<pre>" . $e->getMessage() . "</pre>";
}