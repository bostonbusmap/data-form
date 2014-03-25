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
	 * Get something to produce paginated data. This may be a SQL string, the data itself,
	 * or something else appropriate to the class
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function obtain_paginated_data();

	/**
	 * Get something to produce a row count. This may be a SQL string or the row count itself,
	 * depending on the implementing class
	 *
	 * @return mixed
	 */
	public function obtain_row_count();

	/**
	 * This returns an array of (pagination_data, row_count) in the same format provided
	 * by obtain_paginated_data() and obtain_row_count()
	 *
	 * @return array
	 */
	public function obtain_paginated_data_and_row_count();
}