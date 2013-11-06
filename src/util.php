<?php

// Useful functions for working with DataForm and DataTable objects

/**
 * Create some default datatable columns for a database table
 *
 * @param $res
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

		$column = DataTableColumnBuilder::create()->display_header_name(ucwords($field_name))->column_key($field_name)->build();
		$columns[] = $column;
	}

	return $columns;
}