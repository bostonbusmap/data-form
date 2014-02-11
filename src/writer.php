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

class StdoutWriter implements IWriter {
	protected $stdout;
	public function __construct() {
		$this->stdout = fopen("php://output", "w");
	}

	public function write($s) {
		fwrite($this->stdout, $s);
	}
}
class ResourceWriter implements IWriter {
	protected $resource;
	public function __construct($resource) {
		if (!is_resource($resource)) {
			throw new Exception("f must be a resource");
		}
		$this->resource = $resource;
	}

	public function write($s) {
		fwrite($this->resource, $s);
	}
}