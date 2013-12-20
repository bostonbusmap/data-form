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
 * Class to contain pagination part of DataFormState
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
	 * @param $settings DataTableSettings May be null if unspecified
	 * @return int The current page (starting from 0). If $settings is specified and page is past the end, return the last valid page
	 */
	public function get_current_page($settings) {
		if ($settings === null) {
			return $this->current_page;
		}
		else
		{
			$total_rows = $settings->get_total_rows();
			if ($total_rows === null) {
				return $this->current_page;
			}
			elseif ($this->current_page === null) {
				return $this->current_page;
			}
			else
			{
				if ($this->limit === null) {
					$limit = $settings->get_default_limit();
				}
				else
				{
					$limit = $this->limit;
				}

				if ($limit == 0) {
					$num_pages = 1;
				}
				elseif (($total_rows % $limit) !== 0) {
					$num_pages = (int)(($total_rows / $limit) + 1);
				}
				else
				{
					$num_pages = (int)($total_rows / $limit);
				}

				if ($this->current_page >= $num_pages) {
					if ($num_pages > 1) {
						return $num_pages - 1;
					}
					else
					{
						return 0;
					}
				}
				else
				{
					return $this->current_page;
				}
			}
		}
	}
}
