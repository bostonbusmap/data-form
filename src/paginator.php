<?php

/**
 * Something which can paginate some kind of data
 *
 * Implementing classes must be serializable!
 */
interface IPaginator {
	/**
	 * @param $table_name string
	 * @return SQLBuilder
	 */
	public function table_name($table_name);

	/**
	 * @param $state DataFormState
	 * @return SQLBuilder
	 */
	public function state($state);

	/**
	 * @param $settings DataTableSettings
	 * @return SQLBuilder
	 */
	public function settings($settings);


	/**
	 * If true, no pagination will be done
	 *
	 * @param $ignore_pagination bool
	 * @return SQLBuilder
	 */
	public function ignore_pagination($ignore_pagination = true);

	/**
	 * If true, no filtering will be done
	 *
	 * @param $ignore_filtering bool
	 * @return SQLBuilder
	 */
	public function ignore_filtering($ignore_filtering = true);

	/**
	 * This returns an array of (paginated Iterator, row_count)
	 *
	 * @param $conn_type string
	 * @param $rowid_key string
	 * @return array
	 */
	public function obtain_paginated_data_and_row_count($conn_type, $rowid_key);
}