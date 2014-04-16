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
 * Class to wrap objects which may be selected by default
 */
class Selected {
	/** @var  object Something representable as a string */
	protected $thing;
	/** @var bool Is item selected by default? */
	protected $selected;

	/**
	 * @param $thing mixed Something representable as a string
	 * @param $selected bool Is it selected?
	 * @throws Exception
	 */
	public function __construct($thing, $selected) {
		$this->thing = $thing;
		if (!is_bool($selected)) {
			throw new Exception("selected must be true or false");
		}
		$this->selected = $selected;
	}

	/**
	 * @return object Something representable as string
	 */
	public function get_thing() {
		return $this->thing;
	}

	/**
	 * @return bool Is item selected by default?
	 */
	public function is_selected() {
		return $this->selected;
	}

	public function __toString() {
		return strval($this->thing);
	}
}