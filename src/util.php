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

		$display_header = ucwords(str_replace("_", " ", $field_name));

		$column = DataTableColumnBuilder::create()->display_header_name($display_header)->column_key($field_name)->sortable(true)->build();
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

/**
 * This creates a default DataTable from some SQL
 *
 * @param $sql string SQL to create a table from. This should not already be paginated
 * @param $state DataFormState The state of the form
 * @param string $submit_url The URL the form submits to. If $submit_url is
 * @param string $radio_column_key The column key of a radio to display
 * @return DataTable
 * @throws Exception
 */
function create_table_from_database($sql, $state, $submit_url=null, $radio_column_key=null) {
	if (!is_string($sql) || trim($sql) === "") {
		throw new Exception("sql must be a string of SQL");
	}
	if (!$state || !($state instanceof DataFormState)) {
		throw new Exception("state must exist and must be instanceof DataFormState");
	}
	if ($submit_url && !is_string($submit_url)) {
		throw new Exception("submit must be a URL");
	}
	if ($radio_column_key && !is_string($radio_column_key)) {
		throw new Exception("radio_column_key must be a string");
	}

	$count_sql = SQLBuilder::create($sql)->state($state)->build_count();
	$count_res = gfy_db::query($count_sql, null, true);
	$count_row = gfy_db::fetch_row($count_res);
	$num_rows = (int)$count_row[0];

	$settings = DataTableSettingsBuilder::create()->total_rows($num_rows)->build();
	$paginated_sql = SQLBuilder::create($sql)->settings($settings)->state($state)->build();

	$paginated_res = gfy_db::query($paginated_sql, null, true);

	$data_columns = create_columns_from_database($paginated_res);
	if ($radio_column_key) {
		$checkbox_column = DataTableColumnBuilder::create()->cell_formatter(new DataTableRadioFormatter())->column_key($radio_column_key)->build();
		$columns = array_merge(array($checkbox_column), $data_columns);
	}
	else
	{
		$columns = $data_columns;
	}
	$rows = get_rows_from_database($paginated_res);

	$widgets = array();
	if ($submit_url) {
		$button = DataTableButtonBuilder::create()->text("Submit")->behavior(new DataTableBehaviorSubmit())->form_action($submit_url)->build();
		$widgets[] = $button;
	}

	$table = DataTableBuilder::create()->rows($rows)->columns($columns)->settings($settings)->widgets($widgets)->empty_message("No rows in table")->build();
	return $table;
}

/**
 * Create a default DataForm from sql. It will use the field names as header names
 *
 * @param $sql string
 * @param $state DataFormState
 * @param $submit_url string If this is set, it adds a Submit button submitting the form to this URL
 * @param $radio_column_key string If this is set, it adds an extra radio column at the left for the given field name
 * @return DataForm
 * @throws Exception
 */
function create_data_form_from_database($sql, $state, $submit_url=null, $radio_column_key=null) {
	if (!is_string($sql) || trim($sql) === "") {
		throw new Exception("sql must be a string of SQL");
	}

	$table = create_table_from_database($sql, $state, $submit_url, $radio_column_key);

	$form = DataFormBuilder::create($state->get_form_name())->remote($_SERVER['REQUEST_URI'])->tables(array($table))->build();
	return $form;
}