<?php
interface IWriter {
	/**
	 * @param $s string
	 * @return void
	 */
	function write($s);
}

class StringWriter implements IWriter {
	protected $contents;

	public function __construct() {
		$this->contents = "";
	}

	public function write($s) {
		$this->contents .= $s;
	}

	public function get_contents() {
		return $this->contents;
	}
}

class OutputWriter implements IWriter {
	public function write($s) {
		echo $s;
	}

}