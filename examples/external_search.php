<?php
/**
 * Example to demonstrate search textboxes outside the DataTable
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

require_once "sql_lib.php";


/**
 * Creates a DataForm object for this example
 *
 * @param DataFormState $state Roughly encapsulates $_POST, keeps form consistent between refreshes
 * @return DataForm
 * @throws Exception
 */
function make_external_search_form($state) {
	if (!($state instanceof DataFormState)) {
		throw new Exception("state expected to be instance of DataFormState");
	}

	// generate some SQL
	$browse_searches_query = make_organisms_query();

	// SQLBuilder will parse the SQL (using PHP-SQL-Parser) and store it for future manipulation
	$sql_builder = new SQLBuilder($browse_searches_query);
	// Let SQLBuilder know about $state which contains our search text, pagination and sorting information
	$sql_builder->state($state);
	// Ask SQLBuilder for SQL to count the rows
	$count_query = $sql_builder->build_count();

	$count_res = gfy_db::query($count_query, null, true);
	$row = gfy_db::fetch_row($count_res);
	$num_rows = (int)$row[0];

	// Tell DataTable how many rows we have. This is needed for pagination.
	$settings = DataTableSettingsBuilder::create()
		->total_rows($num_rows)
		->default_limit(10)
		->build();

	// Provide number of rows and limit information to SQLBuilder
	$sql_builder->settings($settings);

	// Create three columns: search_id which is checkboxes to select rows,
	// search_date and search_name. Set search_date to be sortable so we can show off this feature
	$columns = array();
	$columns[] = DataTableColumnBuilder::create()
		->column_key("organism_id")
		->cell_formatter(new DataTableCheckboxCellFormatter())
		->header_formatter(new DataTableCheckboxHeaderFormatter())
		->build();
	$columns[] = DataTableColumnBuilder::create()
		->display_header_name("Organism name")
		->column_key("organism_name")
		->sortable(true)
		->build();
	$columns[] = DataTableColumnBuilder::create()
		->display_header_name("Scientific name")
		->column_key("organism_scientific")
		->build();

	// Make a SQL query which takes into account pagination, filtering and sorting. This will be like our original
	// query but with a LIMIT clause, the search text added in a WHERE clause, and maybe an ORDER BY clause.
	$query = $sql_builder->build();

	// Create the search textbox widget. Use search_type rlike to use regular expressions.
	$widgets = array();

	$widgets[] = DataTableSearchWidgetBuilder::create()
		->column_key("organism_name")
		->search_type(DataTableSearchState::rlike)
		->label("Search organism name: ")
		->build();



	$table = DataTableBuilder::create()
		->columns($columns)
		->rows(new DatabaseIterator($query, null, "organism_name"))
		->settings($settings)
		->widgets($widgets)
		->build();
	$form = DataFormBuilder::create($state->get_form_name())
		->remote("external_search.php")
		->tables(array($table))
		->build();
	return $form;
}

try {
	// $state contains our form state which contains search text among other things
	$state = new DataFormState("organisms", $_GET);
	if ($state->only_display_form()) {
		try
		{
			// If an AJAX request, just display the form.
			// This is currently HTML inside of JSON

			// create our DataForm
			$form = make_external_search_form($state);
			echo $form->display_form($state);
		}
		catch (Exception $e) {
			echo json_encode(array("error" => $e->getMessage()));
		}
	}
	else
	{
		$form = make_external_search_form($state);

		gfy_header("SQL example", "");
		echo $form->display($state);
	}
}
catch (Exception $e) {
	echo "<pre>" . $e . "</pre>";
}