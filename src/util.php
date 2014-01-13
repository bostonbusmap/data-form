<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

require_once FILE_BASE_PATH . "/lib/database_iterator.php";
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
 * Return a paginated SQL query, and set number of rows on $settings (creating $settings if null)
 *
 * @param $query string SQL
 * @param $state DataFormState
 * @param $settings DataTableSettings Table settings. This will become a copy of the input with total_rows
 * set to the number of rows. New default object will be created if this is null.
 * @param $conn_type string Connection type, used for querying for count
 * @param $table_name string Name of table, if more than one table in form
 * @return string SQL
 * @throws Exception
 */
function paginate_sql($query, $state, &$settings, $conn_type=null, $table_name="") {
	if (!is_string($query)) {
		throw new Exception("query must be a string");
	}
	if (!($state instanceof DataFormState)) {
		throw new Exception("state must be instance of DataFormState");
	}
	if ($settings !== null && !($settings instanceof DataTableSettings)) {
		throw new Exception("settings must be instance of DataTableSettings, or null to create a new object");
	}
	if (($conn_type !== null) && !is_string($conn_type)) {
		throw new Exception("conn_type must be a string or null");
	}
	if (!is_string($table_name)) {
		throw new Exception("table_name must be a string");
	}

	// There's a cost to parsing SQL so this object is used twice
	$sql_builder = SQLBuilder::create($query);

	$count_sql = $sql_builder->state($state)->settings($settings)->table_name($table_name)->build_count();
	$count_res = gfy_db::query($count_sql, $conn_type, true);
	$count_row = gfy_db::fetch_row($count_res);
	$num_rows = (int)$count_row[0];

	if ($settings) {
		$settings = $settings->make_builder()->total_rows($num_rows)->build();
	}
	else
	{
		$settings = DataTableSettingsBuilder::create()->total_rows($num_rows)->build();
	}

	$paginated_sql = $sql_builder->settings($settings)->build();
	return $paginated_sql;
}

/**
 * Return a subset of an array given the current pagination state and settings.
 * This also creates a new $settings (or copies the old one) with total_rows set properly
 *
 * @param $array array Full set of rows to be paginated
 * @param $state DataFormState
 * @param $settings DataTableSettings Table settings. This will become a copy of the input with total_rows
 * set to the number of rows. New default object will be created if this is null.
 * @param $table_name string Name of table to paginate, if more than one table in form
 * @return array Paginated subset of array
 * @throws Exception
 */
function paginate_array($array, $state, &$settings, $table_name="") {
	if (!is_array($array)) {
		throw new Exception("array must be an array");
	}
	if (!($state instanceof DataFormState)) {
		throw new Exception("state must be instance of DataFormState");
	}
	if ($settings !== null && !($settings instanceof DataTableSettings)) {
		throw new Exception("settings must be instance of DataTableSettings, or null to create a new object");
	}
	if (!is_string($table_name)) {
		throw new Exception("table_name must be a string");
	}

	// ArrayManager doesn't use the total_rows property of $settings, it gets
	// that information from count($array)
	$manager = new ArrayManager($array);
	$manager->state($state);
	$manager->settings($settings);
	$manager->table_name($table_name);

	list($num_rows, $subset) = $manager->make_filtered_subset();

	// This sets total_rows to give the DataTable information to show pagination controls

	// TODO: move total_rows property somewhere that makes more sense
	if ($settings) {
		$settings = $settings->make_builder()->total_rows($num_rows)->build();
	}
	else
	{
		$settings = DataTableSettingsBuilder::create()->total_rows($num_rows)->build();
	}


	return $subset;
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
function create_table_from_database($sql, $state, $submit_url="", $radio_column_key="") {
	if (!is_string($sql) || trim($sql) === "") {
		throw new Exception("sql must be a string of SQL");
	}
	if (!$state || !($state instanceof DataFormState)) {
		throw new Exception("state must exist and must be instanceof DataFormState");
	}
	if (!is_string($submit_url)) {
		throw new Exception("submit must be a URL");
	}
	if (!is_string($radio_column_key)) {
		throw new Exception("radio_column_key must be a string");
	}

	$paginated_sql = paginate_sql($sql, $state, $settings);

	$paginated_res = gfy_db::query($paginated_sql, null, true);

	$data_columns = create_columns_from_database($paginated_res);
	gfy_db::close($paginated_res);
	
	if ($radio_column_key !== "") {
		$checkbox_column = DataTableColumnBuilder::create()->cell_formatter(new DataTableRadioFormatter())->column_key($radio_column_key)->build();
		$columns = array_merge(array($checkbox_column), $data_columns);
	}
	else
	{
		$columns = $data_columns;
	}
	$rows = new DatabaseIterator($paginated_sql, null, $radio_column_key);

	$widgets = array();
	if ($submit_url !== "") {
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

	// chop off query string. Since the form data goes into the query string leaving it there will complicate things
	$this_url = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);

	$form = DataFormBuilder::create($state->get_form_name())->remote($this_url)->tables(array($table))->build();
	return $form;
}
