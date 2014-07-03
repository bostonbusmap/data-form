<?php

/**
 * Something which can paginate some kind of data
 *
 * Implementing classes must be serializable!
 */
interface IPaginator {

	/**
	 * If true, no pagination will be done
	 *
	 * @param $ignore_pagination bool
	 * @return IPaginator
	 */
	public function ignore_pagination($ignore_pagination = true);

	/**
	 * If true, no filtering will be done
	 *
	 * @param $ignore_filtering bool
	 * @return IPaginator
	 */
	public function ignore_filtering($ignore_filtering = true);

	/**
	 * @param $pagination_info IPaginationInfo
	 * @return IPaginator
	 */
	public function pagination_info($pagination_info);

	/**
	 * This returns an array of (paginated Iterator, row_count)
	 *
	 * @param $conn_type string
	 * @param $rowid_key string
	 * @return array
	 */
	public function obtain_paginated_data_and_row_count($conn_type, $rowid_key);
}