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

/**
 * Creates a SQL query to get searches based on the user currently logged in
 *
 * @return string SQL
 * @throws Exception
 */
function make_external_search_query() {
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
 * Creates a DataForm object for this example
 *
 * @param DataFormState $state Roughly encapsulates $_POST, keeps form consistent between refreshes
 * @return DataForm
 */
function make_external_search_form($state) {
	// generate some SQL
	$browse_searches_query = make_external_search_query();

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
	// search_date and search_name. Set search_date to be sortable so we can show off this feature
	$columns = array();
	$columns[] = DataTableColumnBuilder::create()->column_key("search_id")->
		cell_formatter(new DataTableCheckboxCellFormatter())->
		header_formatter(new DataTableCheckboxHeaderFormatter())->build();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Date created")->column_key("search_date")->sortable(true)->build();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Search name")->column_key("search_name")->build();

	// Make a SQL query which takes into account pagination, filtering and sorting. This will be like our original
	// query but with a LIMIT clause, the search text added in a WHERE clause, and maybe an ORDER BY clause.
	$query = $sql_builder->build();

	// Create the search textbox widget. Use search_type rlike to use regular expressions.
	$widgets = array();

	$widgets[] = DataTableSearchWidgetBuilder::create()->column_key("search_name")->search_type(DataTableSearchState::rlike)->
		label("Search name:")->build();



	$table = DataTableBuilder::create()->columns($columns)->rows(new DatabaseIterator($query, null, "search_id"))->
		settings($settings)->widgets($widgets)->build();
	$form = DataFormBuilder::create($state->get_form_name())->remote($_SERVER["REQUEST_URI"])->
		tables(array($table))->build();
	return $form;
}

try {
	// $state contains our form state which contains search text among other things
	$state = new DataFormState("searches", $_POST);
	// create our DataForm
	$form = make_external_search_form($state);
	// If an AJAX request, just display the form HTML
	if ($state->only_display_form()) {
		echo $form->display_form($state);
	}
	else
	{
		gfy_header("SQL example", "");
		echo $form->display($state);
	}
}
catch (Exception $e) {
	echo "<pre>" . $e . "</pre>";
}