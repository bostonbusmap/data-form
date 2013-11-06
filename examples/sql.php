<?php

require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";
require_once FILE_BASE_PATH . "/www/browser/lib/data_table/sql_builder.php";

/**
 * @param DataFormState $state
 * @return DataForm
 */
function make_form($state) {
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

	$sql_builder = new SQLBuilder($browse_searches_query);
	$sql_builder->state($state);
	$count_query = $sql_builder->build_count();

	$count_res = gfy_db::query($count_query, null, true);
	$row = gfy_db::fetch_row($count_res);
	$num_rows = (int)$row[0];

	$settings = DataTableSettingsBuilder::create()->total_rows($num_rows)->build();

	$columns = array();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Date created")->column_key("search_date")->sortable(true)->build();

	$rows = array();
	$query = $sql_builder->build();
	$res = gfy_db::query($query, null, true);
	while ($row = gfy_db::fetch_assoc($res)) {
		$rows[] = $row;
	}


	$table = DataTableBuilder::create()->columns($columns)->rows($rows)->settings($settings)->build();
	$form = DataFormBuilder::create($state->get_form_name())->remote($_SERVER["REQUEST_URI"])->tables(array($table))->build();
	return $form;
}

try {
	$state = new DataFormState("searches", $_POST);
	$form = make_form($state);
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