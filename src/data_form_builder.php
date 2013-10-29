<?php
class DataFormBuilder {
	/** @var  DataTable[] */
	protected $tables;

	/** @var string */
	private $form_name;


	/** @var  DataFormState[] State received which should be forwarded */
	private $forwarded_state;

	/** @var  string Form submit method, either POST or GET. POST by default */
	private $method;

	public function __construct($form_name) {
		$this->form_name = $form_name;
	}

	/**
	 * @param $form_name string
	 * @return DataFormBuilder
	 */
	public static function create($form_name) {
		return new DataFormBuilder($form_name);
	}

	/**
	 * @param $tables DataTable[]
	 * @return DataTableBuilder
	 */
	public function tables($tables) {
		$this->tables = $tables;
		return $this;
	}

	/**
	 * @param $forwarded_state DataFormState[]
	 * @return DataTableBuilder
	 */
	public function forwarded_state($forwarded_state) {
		$this->forwarded_state = $forwarded_state;
		return $this;
	}

	/**
	 * @param $method string
	 * @return DataFormBuilder
	 */
	public function method($method) {
		$this->method = $method;
		return $this;
	}

	/**
	 * @throws Exception
	 * @return DataForm
	 */
	public function build() {
		if (!$this->form_name) {
			throw new Exception("form_name is blank");
		}

		if (!$this->forwarded_state) {
			$this->forwarded_state = array();
		}
		foreach ($this->forwarded_state as $state) {
			if (!($state instanceof DataFormState)) {
				throw new Exception("forwarded_state must contain instances of DataFormState");
			}
		}

		if (!$this->method) {
			$this->method = "POST";
		}
		elseif (strtolower($this->method) != "get" && strtolower($this->method) != "post") {
			throw new Exception("method must be GET or POST");
		}

		return new DataForm($this);
	}

	public function get_tables()
	{
		return $this->tables;
	}

	public function get_forwarded_state() {
		return $this->forwarded_state;
	}

	public function get_form_name() {
		return $this->form_name;
	}

	public function get_method() {
		return $this->method;
	}
}