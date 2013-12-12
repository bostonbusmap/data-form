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
	 * @return int
	 * The current page (starting from 0)
	 */
	public function get_current_page()
	{
		return $this->current_page;
	}
}
