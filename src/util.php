<?php

// Useful functions for working with DataForm and DataTable objects

/**
 * Create some default datatable columns for a database table
 *
 * @param $res resource
 * @return DataTableColumn[]
 * @throws Exception
 */
function create_columns_from_database($res) {
	if (!is_resource($res)) {
		throw new Exception("res is not an open database connection");
	}

	$num_fields = gfy_db::num_fields($res);
	if (!is_int($num_fields)) {
		throw new Exception("Error getting number of fields");
	}

	$columns = array();
	for ($i = 0; $i < $num_fields; $i++) {
		$field = gfy_db::fetch_field($res, $i);
		/** @var string $field_name */
		$field_name = $field->name;

		$column = DataTableColumnBuilder::create()->display_header_name(ucwords($field_name))->column_key($field_name)->sortable(true)->build();
		$columns[] = $column;
	}

	return $columns;
}

/**
 * @param $res resource
 * @return array
 * @throws Exception
 */
function get_rows_from_database($res) {
	if (!is_resource($res)) {
		throw new Exception("res is not an open database connection");
	}

	$rows = array();
	while ($row = gfy_db::fetch_assoc($res)) {
		$rows[] = $row;
	}
	return $rows;
}

function create_table_from_database($sql, $state, $submit_url=null) {
	if (!$sql || !is_string($sql)) {
		throw new Exception("sql must be a string of SQL");
	}
	if (!$state || !($state instanceof DataFormState)) {
		throw new Exception("state must exist and must be instanceof DataFormState");
	}
	if ($submit_url && !is_string($submit_url)) {
		throw new Exception("submit must be a URL");
	}

	$count_sql = SQLBuilder::create($sql)->state($state)->build_count();
	$count_res = gfy_db::query($count_sql, null, true);
	$count_row = gfy_db::fetch_row($count_res);
	$num_rows = (int)$count_row[0];

	$settings = DataTableSettingsBuilder::create()->total_rows($num_rows)->build();
	$paginated_sql = SQLBuilder::create($sql)->settings($settings)->state($state)->build();

	$columns = create_columns_from_database($paginated_sql, $state);
	$rows = get_rows_from_database($paginated_sql, $state);

	$widgets = array();
	if ($submit_url) {
		$button = DataTableButtonBuilder::create()->text("Submit")->behavior(new DataTableBehaviorSubmit())->form_action($submit_url)->build();
		$widgets[] = $button;
	}

	$table = DataTableBuilder::create()->rows($rows)->columns($columns)->widgets($widgets)->build();
	return $table;
}

/**
 * @param $sql string
 * @param $state DataFormState
 * @return DataForm
 * @throws Exception
 */
function create_data_form_from_database($sql, $state) {
	if (!$sql || !is_string($sql)) {
		throw new Exception("sql must be a string of SQL");
	}

	$table = create_table_from_database($sql, $state);

	$form = DataFormBuilder::create($state->get_form_name())->remote($_SERVER['REQUEST_URI'])->tables(array($table))->build();
	return $form;
}