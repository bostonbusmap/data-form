<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";
require_once FILE_BASE_PATH . "/www/browser/lib/data_table/sql_builder.php";
require_once FILE_BASE_PATH . "/lib/database_iterator.php";
require_once FILE_BASE_PATH . "/lib/output_lib.php";

/**
 * Return SQL showing the searches for the logged in user
 *
 * @return string SQL
 * @throws Exception
 */
function make_organisms_query() {
	$browse_searches_query = "SELECT * FROM organisms";
	return $browse_searches_query;
}

/**
 * Make a form which lists organisms from the database
 *
 * @param DataFormState $state
 * @param string $this_url
 * @return DataForm
 */
function make_organisms_form($state, $this_url) {
	// generate some SQL
	$browse_searches_query = make_organisms_query();

	// Set default number of rows per page
	$settings = DataTableSettingsBuilder::create()
		->default_limit(10)
		->build();

	// Make a SQL query which takes into account pagination, filtering and sorting. This will be like our original
	// query but with a LIMIT clause, the search text added in a WHERE clause, and maybe an ORDER BY clause.

	// This will also replace $settings with a copy which has total_rows set with the number of rows
	$query = paginate_sql($browse_searches_query, $state, $settings);

	// Create three columns: organism_id which is checkboxes to select rows,
	// organism_name and organism_scientific
	// Set organism_name to be sortable so we can show off this feature
	// organism_scientific is set to be searchable
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
		->searchable(true)
		->build();


	// Define the pieces of HTML which surround the DataTable
	$widgets = array();

	// Make two links: Export all rows and Export selected rows
	// The difference between them is the value we set for the field name we just defined

	// Each link sets the hidden field with that field name to the given value,
	// then sets the form action to sql_export.php, then submits the form.
	$widgets[] = DataTableLinkBuilder::create()
		->text("Export all rows")
		->link("sql_export.php")
		->name("export_all_rows")
		->value(true)
		->behavior(new DataTableBehaviorSubmit())
		->build();
	$widgets[] = CustomWidget::create("<br />");
	$widgets[] = DataTableLinkBuilder::create()
		->text("Export selected rows")
		->link("sql_export.php")
		->name("export_selected_rows")
		->value(true)
		->behavior(new DataTableBehaviorSubmit())
		->build();
	$widgets[] = CustomWidget::create("<br />");
	$widgets[] = DataTableButtonBuilder::create()
		->text("Reset")
		->behavior(new DataTableBehaviorReset())
		->build();

	// We need to have a hidden field to set so the value is passed when submitting the form.
	$widgets[] = DataTableHiddenBuilder::create()
		->name("selected_only")
		->build();


	// Create a DataTable from the variables we defined.
	// Note the use of DatabaseIterator() in rows. This runs the query
	// on the database and returns rows. The 'search_id' parameter is the
	// database column name used to provide row keys for the rows parameter.

	// Specifying the row key parameter is important because it allows the DataForm to uniquely identify
	// checkboxes and other input fields, even on different pages.
	$table = DataTableBuilder::create()
		->columns($columns)
		->rows(new DatabaseIterator($query, null, "organism_id"))
		->settings($settings)
		->widgets($widgets)
		->build();
	$form = DataFormBuilder::create($state->get_form_name())
		->remote($this_url)
		->tables(array($table))
		->build();
	return $form;
}

/**
 * Filter the data then write it as a TSV to standard output
 *
 * @param $state DataFormState Form state which contains selected_only
 */
function export_rows($state) {
	// cast string to boolean
	$selected_only = filter_var($state->find_item(array("export_selected_rows")), FILTER_VALIDATE_BOOLEAN);

	// selected_items is a list of search ids
	$selected_items = $state->find_item(array("organism_id"));
	if (!$selected_items) {
		$selected_items = array();
	}

	// We need to filter the data exactly the way it's filtered in the previous page
	// to get a correct data set
	$browse_searches_query = make_organisms_query();
	// SQLBuilder parses the query and makes it ready for manipulation
	$sql_builder = new SQLBuilder($browse_searches_query);
	// Tell SQLBuilder what filtering, pagination, and sorting we did in the previous page
	$sql_builder->state($state);
	// But don't paginate since we're preparing a data set for export
	$sql_builder->ignore_pagination();

	if (!$selected_only || $selected_items) {
		// Filtering behavior might mask rows selected on unfiltered row set, so turn it off
		$sql_builder->ignore_filtering();
	}
	// make a new SQL query that accounts for these concerns
	$query = $sql_builder->build();

	// Make data set, filtering selected rows manually
	$rows = array();
	foreach (new DatabaseIterator($query) as $row) {
		$search_id = (string)$row["organism_id"];

		// if 'Export all rows' or if nothing is selected, or if we're on a selected item
		if (!$selected_only || !$selected_items || in_array($search_id, $selected_items)) {
			$rows[] = array(
				"organism_name" => $row["organism_name"],
				"organism_scientific" => $row["organism_scientific"]
			);
		}
	}

	// write TSV
	Output::write_tsv($rows, "export_searches.tsv");
}
