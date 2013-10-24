<?php
class DataFormBuilder {
	/** @var  DataTable[] */
	protected $tables;

	/** @var string */
	private $form_name;


	/** @var  DataFormState[] State received which should be forwarded */
	private $forwarded_state;

	public function __construct($form_name) {
		$this->form_name = $form_name;
	}

	public static function create($form_name) {
		return new DataFormBuilder($form_name);
	}

	public function tables($tables) {
		$this->tables = $tables;
		return $this;
	}

	public function forwarded_state($forwarded_state) {
		$this->forwarded_state = $forwarded_state;
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
}