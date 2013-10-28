<?php
/**
 * Convenience class to contain pagination state
 */
class DataTablePagination {
	const limit_key = "_limit";
	const current_page_key = "_current_page";

	const default_limit = 25;

	/** @var  int|null Number of rows per page, or falsey for all rows on one page */
	protected $limit;
	/** @var  int The current page */
	protected $current_page;

	public function __construct($array) {
		if (!$array) {
			$array = array(self::limit_key => 0,
				self::current_page_key => 0);
		}
		if (array_key_exists(self::limit_key, $array) && $array[self::limit_key]) {
			$this->limit = (int)$array[self::limit_key];
		}
		else {
			$this->limit = 0;
		}
		if (array_key_exists(self::current_page_key, $array) && $array[self::current_page_key]) {
			$this->current_page = (int)$array[self::current_page_key];
		}
		else
		{
			$this->current_page = 0;
		}
	}

	/**
	 * @return int|null
	 * Number of rows per page, or falsey if all rows
	 */
	public function get_limit()
	{
		return $this->limit;
	}

	/**
	 * @return int
	 * The current page
	 */
	public function get_current_page()
	{
		return $this->current_page;
	}

	/**
	 * @param string $form_name
	 * @param DataFormState $state
	 * @param string $remote_url
	 * @param string $table_name
	 * @param int $num_rows Number of rows
	 * @return string
	 */
	public static function create_pagination_controls($form_name, $state, $remote_url, $table_name, $num_rows) {
		$ret = "";
		$ret .= self::create_pagination_limit_controls($form_name, $state, $remote_url, $table_name);
		$ret .= self::create_pagination_page_controls($form_name, $state, $remote_url, $table_name, $num_rows);

		return $ret;
	}

	/**
	 * @param $form_name string
	 * @param DataFormState $state
	 * @param $remote_url string
	 * @param $table_name string
	 * @return string HTML
	 */
	public static function create_pagination_limit_controls($form_name, $state, $remote_url, $table_name) {
		$ret = "<div style='float:right;'>";
		$ret .= "limit: ";

		$option_values = array();
		$option_values[] = new DataTableOption("ALL", 0, false);
		foreach (array(10, 25, 50, 100, 500, 1000) as $value) {
			if ($value == self::default_limit) {
				$selected = true;
			}
			else
			{
				$selected = false;
			}
			$option_values[] = new DataTableOption($value, $value, $selected);
		}
		$limit_name_array = array_merge(DataFormState::get_pagination_state_key($table_name), array(self::limit_key));
		$limit_name = DataFormState::make_field_name($form_name, $limit_name_array);
		$form_action = $remote_url;

		$behavior = new DataTableBehaviorRefresh();
		$options = new DataTableOptions($option_values, $limit_name, $form_action, $behavior);

		$ret .= $options->display($form_name, $state);
		$ret .= "</div>";
		return $ret;
	}

	/**
	 * @param string $form_name
	 * @param DataFormState $state
	 * @param string $remote_url
	 * @param string $table_name
	 * @param int $num_rows
	 * @return string HTML
	 */
	public static function create_pagination_page_controls($form_name, $state, $remote_url, $table_name, $num_rows) {
		$ret = "<div>";
		$pagination = $state->get_pagination_state($table_name);

		// number of nearby pages to show
		$window = 5;

		$current_page_name_array = array_merge(DataFormState::get_pagination_state_key($table_name), array(self::current_page_key));
		$current_page_name = DataFormState::make_field_name($form_name, $current_page_name_array);

		$current_page = $pagination->current_page;
		if ($pagination->limit == 0) {
			$num_pages = 1;
		}
		elseif ($num_rows % $pagination->limit != 0) {
			$num_pages = ($num_rows / $pagination->limit) + 1;
		}
		else
		{
			$num_pages = $num_rows / $pagination->limit;
		}

		// note that current_page is 0-indexed
		if ($current_page > 0) {
			// there is a previous page
			$onclick_previous_obj = new DataTableBehaviorRefresh($current_page_name. "=" . ($current_page - 1));
			$onclick_previous = $onclick_previous_obj->action($form_name, $remote_url);
			$ret .= "<a href='#' onclick='$onclick_previous' title='Go to previous page'>&laquo; Previous</a> ";
		}
		else
		{
			$ret .= "&laquo; Previous ";
		}

		if ($current_page - ($window/2) > 1) {
			$ret .= " &hellip; ";
		}

		for ($page_num = $current_page - ($window/2); $page_num <= $current_page - ($window / 2); $page_num++) {
			if ($page_num < 0 || $page_num >= $num_pages) {
				continue;
			}
			if ($page_num == $current_page) {
				$ret .= " " . ($page_num + 1) . " ";
			}
			else
			{
				$behavior = new DataTableBehaviorRefresh($current_page_name . "=" . $page_num);
				$page_onclick = $behavior->action($form_name, $remote_url);
				$link_title = "Go to page " . ($page_num + 1) . " of " . ($num_pages);
				$ret .= " <a href='#' onclick='$page_onclick' title='$link_title'>" . ($page_num + 1) . "</a> ";
			}
		}

		if ($current_page < $num_pages - 1) {
			// there is a previous page
			$onclick_next_obj = new DataTableBehaviorRefresh($current_page_name. "=" . ($current_page + 1));
			$onclick_next = $onclick_next_obj->action($form_name, $remote_url);
			$ret .= "<a href='#' onclick='$onclick_next' title='Go to next page'>Next &raquo;</a> ";
		}
		$ret .= "</div>";
		return $ret;
	}

}