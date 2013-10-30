<?php

/**
 * Settings supplied by user
 */
class DataTablePaginationSettings {
	/** @var  int */
	protected $default_limit;
	/** @var  int */
	protected $total_rows;

	/** @var string[] Mapping of limit number to text to display for that limit number */
	protected $limit_options;

	/**
	 * @param $default_limit int The default number of rows per page. If 0, show all rows
	 * @param $total_rows int The total number of rows available
	 * @param $limit_options string[] Mapping of limit number to text to display for that limit number. Null for default limit options
	 * @throws Exception
	 */
	public function __construct($default_limit, $total_rows, $limit_options=null) {
		if (!is_int($default_limit)) {
			throw new Exception("default_limit must be a number");
		}
		$this->default_limit = $default_limit;
		if (!is_int($total_rows)) {
			throw new Exception("total_rows must be a number");
		}
		$this->total_rows = $total_rows;

		if (is_null($limit_options)) {
			$limit_options = array(
				0 => "ALL",
				10 => "10",
				25 => "25",
				50 => "50",
				100 => "100",
				500 => "500",
				1000 => "1000"
			);
		}
		$this->limit_options = $limit_options;
	}

	/**
	 * @return int
	 */
	public function get_default_limit() {
		return $this->default_limit;
	}

	/**
	 * @return int
	 */
	public function get_total_rows() {
		return $this->total_rows;
	}

	/**
	 * @return string[] Mapping of limit number to text to display for that limit number. Null for default limit options
	 */
	public function get_limit_options() {
		return $this->limit_options;
	}

	/**
	 * @param string $form_name
	 * @param string $form_method GET or POST
	 * @param DataFormState $state
	 * @param string $remote_url
	 * @param string $table_name
	 * @return string
	 */
	public function display_controls($form_name, $form_method, $state, $remote_url, $table_name) {
		$ret = "";
		$ret .= $this->create_pagination_limit_controls($form_name, $form_method, $state, $remote_url, $table_name);
		$ret .= $this->create_pagination_page_controls($form_name, $form_method, $state, $remote_url, $table_name);

		return $ret;
	}

	/**
	 * @param $form_name string
	 * @param $form_method string GET or POST
	 * @param DataFormState $state
	 * @param $remote_url string
	 * @param $table_name string
	 * @return string HTML
	 */
	protected function create_pagination_limit_controls($form_name, $form_method, $state, $remote_url, $table_name) {
		$ret = "<div style='float:right;'>";
		$ret .= "limit: ";

		$option_values = array();

		$options = $this->get_limit_options();

		$default_pagination_limit = $this->get_default_limit();

		foreach ($options as $limit => $text) {
			$option_values[] = new DataTableOption($text, $limit, $limit === $default_pagination_limit);
		}
		$limit_name_array = array_merge(DataFormState::get_pagination_state_key($table_name),
			array(DataTablePaginationState::limit_key));
		$form_action = $remote_url;

		$behavior = new DataTableBehaviorRefresh();

		$ret .= DataTableOptions::display_options($form_name, $limit_name_array, $form_action, $form_method, $behavior, $option_values, $state);
		$ret .= "</div>";
		return $ret;
	}

	/**
	 * @param $page_num int
	 * @param $text string
	 * @param $title string
	 * @param $form_name string
	 * @param $remote_url string
	 * @param $form_method string GET or POST
	 * @param $table_name string
	 * @return string HTML
	 */
	protected function create_page_link($page_num, $text, $title, $form_name, $remote_url, $form_method, $table_name) {
		$current_page_name_array = array_merge(DataFormState::get_pagination_state_key($table_name),
			array(DataTablePaginationState::current_page_key));
		$current_page_name = DataFormState::make_field_name($form_name, $current_page_name_array);
		$behavior = new DataTableBehaviorRefresh($current_page_name. "=" . $page_num);
		$onclick = $behavior->action($form_name, $remote_url, $form_method);

		return " <a href='#' onclick='$onclick' title='$title'>$text</a> ";
	}

	/**
	 * @param string $form_name
	 * @param string $form_method GET or POST
	 * @param DataFormState $state
	 * @param string $remote_url
	 * @param string $table_name
	 * @return string HTML
	 * @throws Exception
	 */
	protected function create_pagination_page_controls($form_name, $form_method, $state, $remote_url, $table_name) {
		$ret = "<div style='text-align: left;'>";
		if ($state) {
			$pagination_state = $state->get_pagination_state($table_name);
		}
		else
		{
			$pagination_state = null;
		}

		// number of nearby pages to show
		$window = 5;

		if ($pagination_state) {
			$current_page = $pagination_state->get_current_page();
		}
		else
		{
			$current_page = 0;
		}

		if (!$pagination_state || is_null($pagination_state->get_limit())) {
			$limit = $this->get_default_limit();
		}
		else {
			$limit = $pagination_state->get_limit();
		}

		$num_rows = $this->get_total_rows();
		if ($limit == 0) {
			$num_pages = 1;
		}
		elseif (($num_rows % $limit) !== 0) {
			$num_pages = (int)(($num_rows / $limit) + 1);
		}
		else
		{
			$num_pages = (int)($num_rows / $limit);
		}

		// note that current_page is 0-indexed
		if ($current_page > 0) {
			// there is a previous page
			$ret .= $this->create_page_link($current_page - 1, "&laquo; Previous", "Go to previous page",
				$form_name, $remote_url, $form_method, $table_name);
		}
		else
		{
			$ret .= "&laquo; Previous ";
		}

		$starting_page = $current_page - (int)($window/2);
		if ($starting_page < 0) {
			$starting_page = 0;
		}
		$ending_page = $starting_page + $window;

		if ($starting_page > 0) {
			$ret .= $this->create_page_link(0, "1", "Go to first page",
				$form_name, $remote_url, $form_method, $table_name);
			$ret .= " ... ";
		}


		for ($page_num = $starting_page; $page_num < $ending_page; $page_num++) {
			if ($page_num < 0 || $page_num >= $num_pages) {
				continue;
			}
			if ($page_num == $current_page) {
				$ret .= " " . ($page_num + 1) . " ";
			}
			else
			{
				$link_title = "Go to page " . ($page_num + 1) . " of " . ($num_pages);
				$ret .= $this->create_page_link($page_num, (string)($page_num + 1), $link_title,
					$form_name, $remote_url, $form_method, $table_name);
			}
		}

		if ($ending_page < $num_pages) {
			$ret .= " ... ";
			$ret .= $this->create_page_link($num_pages - 1, (string)($num_pages), "Go to last page",
				$form_name, $remote_url, $form_method, $table_name);
		}

		if ($current_page < $num_pages - 1) {
			// there is a next page
			$ret .= $this->create_page_link($current_page + 1, "Next &raquo; ", "Go to next page",
				$form_name, $remote_url, $form_method, $table_name);
		}
		else
		{
			$ret .= " Next &raquo; ";
		}

		// write out current page as hidden field
		$current_page_name_array = array_merge(DataFormState::get_pagination_state_key($table_name),
			array(DataTablePaginationState::current_page_key));
		$current_page_name = DataFormState::make_field_name($form_name, $current_page_name_array);
		$ret .= "<input type='hidden' name='$current_page_name' value='$current_page' />";
		$ret .= "</div>";
		return $ret;
	}

}

/**
 * Class to contain pagination state
 */
class DataTablePaginationState {
	const limit_key = "_limit";
	const current_page_key = "_current_page";

	/** @var  int Number of rows per page, or 0 for all rows */
	protected $limit;
	/** @var  int The current page (0-indexed) */
	protected $current_page;

	/**
	 * @param $array array Array from $_POST with pagination data
	 */
	public function __construct($array) {
		if (!$array) {
			$array = array(self::limit_key => null,
				self::current_page_key => 0);
		}
		if (array_key_exists(self::limit_key, $array) && !is_null($array[self::limit_key])) {
			$this->limit = (int)$array[self::limit_key];
		}
		else {
			$this->limit = null;
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
	 * Number of rows per page, or 0 if all rows. May be null if unset
	 */
	public function get_limit()
	{
		return $this->limit;
	}

	/**
	 * @return int
	 * The current page (starting from 0)
	 */
	public function get_current_page()
	{
		return $this->current_page;
	}
}
