<?php
/**
 * Class to wrap objects which may be selected by default
 */
class Selected {
	/** @var  object Something representable as a string */
	protected $thing;
	/** @var bool  */
	protected $selected;

	public function __construct($thing, $selected) {
		$this->thing = $thing;
		if (!is_bool($selected)) {
			throw new Exception("selected must be true or false");
		}
		$this->selected = $selected;
	}

	public function get_thing() {
		return $this->thing;
	}

	public function is_selected() {
		return $this->selected;
	}

	public function __toString() {
		return strval($this->thing);
	}
}