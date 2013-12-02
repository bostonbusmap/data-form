<?php

require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";
require_once FILE_BASE_PATH . "/www/browser/lib/data_table/sql_builder.php";
require_once FILE_BASE_PATH . "/lib/database_iterator.php";

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
 * @return DataForm
 */
function make_searches_form($state) {
	$browse_searches_query = make_searches_query();
	$sql_builder = new SQLBuilder($browse_searches_query);
	$sql_builder->state($state);
	$count_query = $sql_builder->build_count();

	$count_res = gfy_db::query($count_query, null, true);
	$row = gfy_db::fetch_row($count_res);
	$num_rows = (int)$row[0];

	$settings = DataTableSettingsBuilder::create()->total_rows($num_rows)->build();

	$columns = array();
	$columns[] = DataTableColumnBuilder::create()->column_key("search_id")->
		cell_formatter(new DataTableCheckboxCellFormatter())->
		header_formatter(new DataTableCheckboxHeaderFormatter())->build();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Date created")->column_key("search_date")->sortable(true)->build();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Search name")->column_key("search_name")->searchable(true)->build();

	$query = $sql_builder->build();

	$widgets = array();

	// we need to have a hidden field to set so the value can be passed
	$widgets[] = DataTableHiddenBuilder::create()->name("selected_only")->build();

	$selected_only_name = DataFormState::make_field_name($state->get_form_name(), array("selected_only"));

	$widgets[] = DataTableLinkBuilder::create()->text("Export all rows")->link("sql_export.php")->
		behavior(new DataTableBehaviorSetParamsThenSubmit(array($selected_only_name => false)))->build();
	$widgets[] = DataTableLinkBuilder::create()->text("Export selected rows")->link("sql_export.php")->
		behavior(new DataTableBehaviorSetParamsThenSubmit(array($selected_only_name => true)))->build();



	$table = DataTableBuilder::create()->columns($columns)->rows(new DatabaseIterator($query))->settings($settings)->widgets($widgets)->build();
	$form = DataFormBuilder::create($state->get_form_name())->remote($_SERVER["REQUEST_URI"])->tables(array($table))->build();
	return $form;
}

/**
 * @param $state DataFormState
 */
function export_rows($state) {
	$selected_only = $state->find_item(array("selected_only"));
	$selected_only = filter_var($selected_only, FILTER_VALIDATE_BOOLEAN);


	$browse_searches_query = make_searches_query();
	$sql_builder = new SQLBuilder($browse_searches_query);
	$sql_builder->state($state);
	$sql_builder->ignore_pagination();
	$query = $sql_builder->build();

	header("Content-Disposition: attachment; filename=\"export_searches.tsv\"");
	header("Content-Type: text/tab-delimited-values");

	$headers = array("search_date", "search_name");
	$rows = array();
	$selected_items = $state->find_item(array("search_id"));
	if (!$selected_items) {
		$selected_items = array();
	}
	foreach (new DatabaseIterator($query) as $row) {
		$search_id = (string)$row["search_id"];

		// if 'Export all rows' or if nothing is selected, or if we're on a selected item
		if (!$selected_only || !$selected_items || in_array($search_id, $selected_items)) {
			$rows[] = array($row["search_date"], $row["search_name"]);
		}
	}

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
