<?php

/**
 * Sorting state for a particular column
 */
class DataTableSortingState
{
	const sort_type_numeric = "numeric";
	const sort_type_text = "text";
	const sort_type_default = "";

	const sort_order_desc = "desc";
	const sort_order_asc = "asc";
	const sort_order_default = "";

	/**
	 * Field specifying type of sort
	 */
	const type_key = "_t";
	/**
	 * Field specifying direction of sort
	 */
	const direction_key = "_d";

	public function __construct($type, $direction)
	{
		if ($type !== self::sort_type_numeric &&
			$type !== self::sort_type_text &&
			$type !== self::sort_type_default) {
			throw new Exception("Unknown sort type $type");
		}

		if ($direction !== self::sort_order_desc &&
			$direction !== self::sort_order_asc &&
			$direction !== self::sort_order_default) {
			throw new Exception("Unknown sort direction $direction");
		}

		$this->type = $type;
		$this->direction = $direction;
	}

	public function get_type() {
		return $this->type;
	}

	public function get_direction() {
		return $this->direction;
	}

	/**
	 * @return bool
	 */
	public function is_active() {
		return $this->direction !== self::sort_order_default;
	}
}