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

/**
 * Return SQL showing the searches for the logged in user
 *
 * @return string SQL
 * @throws Exception
 */
function make_searches_query() {
	verify_login();

	$current_user = user::get_current_user();
	if (!$current_user) {
		throw new Exception("user is not logged in");
	}
	$user_id = (int)$current_user->get_id();
	$browse_searches_query = "SELECT DISTINCT
		s.search_perscan_table_name,
		s.search_date,
		s.run_id AS rid,
		s.search_id,
		s.search_name AS search_name,
		sa.search_algorithm_type AS stype,
		s.search_notes AS snotes,
		sc.scans_name AS scans_name,
		sc.scans_id AS scid,
		r.run_name AS run_name,
		r.run_status as run_status,
		s.search_peptide_mass_units as mass_units,
		a.access_type AS acc_type,
		u.user_name,
		GROUP_CONCAT(DISTINCT run_conn_table_type) AS tables
	FROM
		`access` AS a,
		`scans` AS sc,
		`runs` AS r,
		`search_algorithm` AS sa,
		users AS u,
		`searches` AS s LEFT JOIN runs_connector AS rc ON s.run_id = rc.run_id
	WHERE
		a.user_id = $user_id AND
		a.access_id = s.access_id
		AND s.scans_id = sc.scans_id
		AND u.user_id=a.user_id
		AND sa.search_algorithm_id = s.search_algorithm_id
		AND s.run_id = r.run_id
		AND s.search_perscan_table_name IS NOT NULL
		AND s.search_perhit_table_name IS NOT NULL
	GROUP BY s.search_id
";
	return $browse_searches_query;
}

/**
 * @param DataFormState $state
 * @param string $this_url
 * @return DataForm
 */
function make_searches_form($state, $this_url) {
	// generate some SQL
	$browse_searches_query = make_searches_query();

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
	$settings = DataTableSettingsBuilder::create()->total_rows($num_rows)->build();

	// Create three columns: search_id which is checkboxes to select rows,
	// search_date and search_name.
	// Set search_date to be sortable so we can show off this feature
	// search_name is set to be searchable
	$columns = array();
	$columns[] = DataTableColumnBuilder::create()->column_key("search_id")->
		cell_formatter(new DataTableCheckboxCellFormatter())->
		header_formatter(new DataTableCheckboxHeaderFormatter())->build();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Date created")->column_key("search_date")->sortable(true)->build();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Search name")->column_key("search_name")->searchable(true)->build();

	// Make a SQL query which takes into account pagination, filtering and sorting. This will be like our original
	// query but with a LIMIT clause, the search text added in a WHERE clause, and maybe an ORDER BY clause.
	$query = $sql_builder->build();

	// Define the pieces of HTML which surround the DataTable
	$widgets = array();

	// Make the field name for 'selected_only', which will be 'searches[selected_only]'
	// We need that field name to use it in an AJAX request to export rows
	$selected_only_name = DataFormState::make_field_name($state->get_form_name(), array("selected_only"));

	// Make two links: Export all rows and Export selected rows
	// The difference between them is the value we set for the field name we just defined

	// Each link sets the hidden field with that field name to the given value,
	// then sets the form action to sql_export.php, then submits the form.
	$widgets[] = DataTableLinkBuilder::create()->text("Export all rows")->link("sql_export.php")->
		behavior(new DataTableBehaviorSetParamsThenSubmit(array($selected_only_name => false)))->build();
	$widgets[] = DataTableLinkBuilder::create()->text("Export selected rows")->link("sql_export.php")->
		behavior(new DataTableBehaviorSetParamsThenSubmit(array($selected_only_name => true)))->build();

	// We need to have a hidden field to set so the value is passed when submitting the form.
	$widgets[] = DataTableHiddenBuilder::create()->name("selected_only")->build();


	// Create a DataTable from the variables we defined.
	// Note the use of DatabaseIterator() in rows. This runs the query
	// on the database and returns rows. The 'search_id' parameter is the
	// database column name used to provide row keys for the rows parameter.

	// Specifying the row key parameter is important because it allows the DataForm to uniquely identify
	// checkboxes and other input fields, even on different pages.
	$table = DataTableBuilder::create()->columns($columns)->rows(new DatabaseIterator($query, null, "search_id"))->settings($settings)->widgets($widgets)->build();
	$form = DataFormBuilder::create($state->get_form_name())->remote($this_url)->tables(array($table))->build();
	return $form;
}

/**
 *
 *
 * @param $state DataFormState Form state which contains selected_only
 */
function export_rows($state) {
	$selected_only = filter_var($state->find_item(array("selected_only")), FILTER_VALIDATE_BOOLEAN);

	// selected_items is a list of search ids
	$selected_items = $state->find_item(array("search_id"));
	if (!$selected_items) {
		$selected_items = array();
	}

	// We need to filter the data the way it's filtered in the previous page
	// to get a correct data set
	$browse_searches_query = make_searches_query();
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

	// Prepare to write TSV to output
	header("Content-Disposition: attachment; filename=\"export_searches.tsv\"");
	header("Content-Type: text/tab-delimited-values");

	// Make data set, filtering selected rows manually
	// We could filter in SQL instead if the number of rows became a problem
	$headers = array("search_date", "search_name");
	$rows = array();
	foreach (new DatabaseIterator($query) as $row) {
		$search_id = (string)$row["search_id"];

		// if 'Export all rows' or if nothing is selected, or if we're on a selected item
		if (!$selected_only || ($selected_items && in_array($search_id, $selected_items))) {
			$rows[] = array($row["search_date"], $row["search_name"]);
		}
	}

	// write TSV
	$stdout = fopen("php://output", "w");
	fwritetsv($stdout, $rows, $headers);

}

/**
 * Write a TSV to a file. Exception is thrown on failure
 *
 * Originally from tmt_stats/lib/util.php
 *
 * @param $f resource Opened file resource
 * @param $data array Rows of data as array of arrays
 * @param $headers string[] Headers to write
 * @throws Exception
 */
function fwritetsv($f, $data, $headers) {
	if (!$f || !is_resource($f)) {
		throw new Exception("f must be a resource");
	}
	if (!$headers) {
		throw new Exception("Headers are missing");
	}
	if (!is_array($headers)) {
		throw new Exception("headers must be an array");
	}
	if (!is_array($data)) {
		throw new Exception("data must be an array");
	}

	fwrite($f, join("\t", $headers));
	fwrite($f, "\n");

	$row_count = 0;
	foreach ($data as $row) {
		if (count($row) != count($headers)) {
			throw new Exception("row #" . ($row_count + 1) . " has " . count($row) . " columns but header has " . count($headers) . " columns");
		}

		fwrite($f, join("\t", $row));
		fwrite($f, "\n");

		$row_count++;
	}
}
