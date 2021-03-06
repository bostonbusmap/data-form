<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

/**
 * Default settings for DataTable pagination, filtering and sorting. Will be overridden by settings in DataFormState if they exist
 */
class DataTableSettings {
	const default_limit = 25;

	/** @var  int Default number of rows per page */
	protected $default_limit;
	/** @var  int|null Number of rows in data set. Used to calculate pagination. May be null if unknown */
	protected $total_rows;

	/** @var string[] Mapping of limit number to text to display for that limit number */
	protected $limit_options;

	/**
	 * @var array mapping of column_key to DataTableSortingState
	 */
	protected $sorting;

	/**
	 * @var DataTableSearchState[] mapping of column_key to search state
	 */
	protected $filtering;

	/**
	 * Turn off pagination if true
	 *
	 * @var bool
	 */
	protected $no_pagination;

	/**
	 * Use DataTableSettingsBuilder::build()
	 *
	 * @param $builder DataTableSettingsBuilder
	 * @throws Exception
	 */
	public function __construct($builder) {
		if (!($builder instanceof DataTableSettingsBuilder)) {
			throw new Exception("builder expected to be instance of DataTableSettingsBuilder");
		}
		$this->default_limit = $builder->get_default_limit();
		$this->total_rows = $builder->get_total_rows();
		$this->limit_options = $builder->get_limit_options();
		$this->sorting = $builder->get_sorting();
		$this->filtering = $builder->get_filtering();
		$this->no_pagination = $builder->get_no_pagination();
	}

	/**
	 * Creates a DataTableSettingsBuilder from the contents of this class, so the user can
	 * modify it and create a copy
	 *
	 * @return DataTableSettingsBuilder
	 */
	public function make_builder() {
		$builder = DataTableSettingsBuilder::create()->default_limit($this->default_limit)->
			total_rows($this->total_rows)->
			limit_options($this->limit_options);
		foreach ($this->sorting as $key => $value) {
			$builder->sort_by($key, $value);
		}
		foreach ($this->filtering as $key => $value) {
			$builder->filter_by($key, $value);
		}
		$builder->no_pagination($this->no_pagination);

		return $builder;
	}

	/**
	 * Returns HTML for pagination controls. Meant for use by DataTable
	 *
	 * @param string $form_name Name of form
	 * @param string $form_method GET or POST
	 * @param DataFormState $state Form state containing pagination information
	 * @param string $remote_url URL to refresh to
	 * @param string $table_name Name of table containing pagination controls (if any)
	 * @throws Exception
	 * @return string
	 */
	public function display_controls($form_name, $form_method, $state, $remote_url, $table_name) {
		$ret = "";
		$ret .= $this->create_pagination_limit_controls($form_name, $form_method, $state, $remote_url, $table_name);
		$ret .= $this->create_pagination_page_controls($form_name, $form_method, $state, $remote_url, $table_name);

		return $ret;
	}

	/**
	 * Display limit select element
	 *
	 * @param $form_name string Name of form
	 * @param $form_method string GET or POST
	 * @param DataFormState $state State with pagination information
	 * @param $remote_url string URL for refresh
	 * @param $table_name string Name of table if any
	 * @throws Exception
	 * @return string HTML
	 */
	protected function create_pagination_limit_controls($form_name, $form_method, $state, $remote_url, $table_name) {
		$ret = "<div style='float:right;'>";

		$option_values = array();

		$options = $this->limit_options;

		$default_pagination_limit = $this->default_limit;

		foreach ($options as $limit => $text) {
			$option_values[] = new DataTableOption($text, $limit, $limit === $default_pagination_limit);
		}
		$limit_name_array = array_merge(DataFormState::get_pagination_state_key($table_name),
			array(DataFormState::limit_key));
		$form_action = $remote_url;

		$behavior = new DataTableBehaviorRefresh();

		$ret .= DataTableOptions::display_options($form_name, $limit_name_array, $form_action, $form_method, $behavior, $option_values, "limit: ", $state);
		$ret .= "</div>";
		return $ret;
	}

	/**
	 * Display single page link
	 *
	 * @param $page_num int Page number
	 * @param $text string Text of page link (for example, 'Next', '2', 'Prev')
	 * @param $title string Mouseover title
	 * @param $form_name string Name of form
	 * @param $remote_url string URL to refresh from
	 * @param $form_method string GET or POST
	 * @param $table_name string Name of table if any
	 * @throws Exception
	 * @return string HTML
	 */
	protected function create_page_link($page_num, $text, $title, $form_name, $remote_url, $form_method, $table_name) {
		$current_page_name_array = array_merge(DataFormState::get_pagination_state_key($table_name),
			array(DataTablePaginationState::current_page_key));
		$current_page_name = DataFormState::make_field_name($form_name, $current_page_name_array);
		$behavior = new DataTableBehaviorRefresh(array($current_page_name => $page_num));
		$onclick = $behavior->action($form_name, $remote_url, $form_method);

		return ' <a href="#" onclick="' . htmlspecialchars($onclick) . '" title="' . htmlspecialchars($title) . '">' . $text . '</a> ';
	}

	/**
	 * Display page links
	 *
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

		// number of nearby pages to show
		// TODO: make this a parameter
		$window = 5;

		$settings = $this;
		$pagination_info = DataFormState::make_pagination_info($state, $settings, $table_name);

		$num_rows = $settings->get_total_rows();
		$current_page = $pagination_info->calculate_current_page($num_rows);

		$num_pages = $pagination_info->calculate_num_pages($num_rows);

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
			if ($page_num < 0 || ($num_pages !== null && $page_num >= $num_pages)) {
				continue;
			}
			if ($page_num == $current_page) {
				$ret .= " " . ($page_num + 1) . " ";
			}
			else
			{
				if ($num_pages !== null) {
					$link_title = "Go to page " . ($page_num + 1) . " of " . ($num_pages);
				}
				else
				{
					$link_title = "Go to page " . ($page_num + 1);
				}
				$ret .= $this->create_page_link($page_num, (string)($page_num + 1), $link_title,
					$form_name, $remote_url, $form_method, $table_name);
			}
		}

		if ($num_pages === null) {
			$ret .= " ... ";
		}
		elseif ($ending_page < $num_pages) {
			$ret .= " ... ";
			$ret .= $this->create_page_link($num_pages - 1, (string)($num_pages), "Go to last page",
				$form_name, $remote_url, $form_method, $table_name);
		}

		if ($num_pages === null || ($current_page < $num_pages - 1)) {
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
		$ret .= '<input type="hidden" name="' . htmlspecialchars($current_page_name) . '" value="' . htmlspecialchars($current_page) . '" />';
		$ret .= "</div>";
		return $ret;
	}

	/**
	 * Did user indicate that they want pagination?
	 * @return bool
	 */
	public function uses_pagination()
	{
		return !$this->no_pagination;
	}

	/**
	 * Default number of rows per page
	 *
	 * @return int
	 */
	public function get_default_limit()
	{
		return $this->default_limit;
	}

	/**
	 * Number of rows in data set. May be null if unknown
	 *
	 * @return int|null
	 */
	public function get_total_rows() {
		return $this->total_rows;
	}

	/**
	 * Columns which are sorted by default, and how
	 *
	 * @return array Map of column_key to DataTableSortingState
	 */
	public function get_default_sorting()
	{
		return $this->sorting;
	}

	/**
	 * How columns are filtered by default
	 *
	 * @return DataTableSearchState[] Map of column_key to some search state to show by default
	 */
	public function get_default_filtering() {
		return $this->filtering;
	}

	/**
	 * Is pagination disabled?
	 *
	 * @return bool
	 */
	public function get_no_pagination() {
		return $this->no_pagination;
	}
}