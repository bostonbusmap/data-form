<?php
/**
 * Represents HTML option element. Use with DataTableOptions
 */
class DataTableOption {
	/** @var  string */
	protected $text;
	/** @var  string */
	protected $value;
	/** @var  bool */
	protected $selected;

	public function __construct($text, $value, $selected=false) {
		$this->text = $text;
		if (is_int($value)) {
			$value = (string)$value;
		}
		$this->value = $value;
		$this->selected = $selected;
	}

	public function get_text() {
		return $this->text;
	}

	public function get_value() {
		return $this->value;
	}

	public function is_selected() {
		return $this->selected;
	}

	/**
	 * @param $override_select bool|null Either override with true or false, or null if no override
	 * @return string HTML
	 */
	public function display($override_select) {
		$value = $this->value;
		$text = $this->text;
		$selected = $this->selected;

		if (!is_null($override_select)) {
			$selected = $override_select;
		}
		if ($selected) {
			return '<option value="' . htmlspecialchars($value) . '" selected>' . $text . "</option>";
		}
		else
		{
			return '<option value="' . htmlspecialchars($value) . '">' . $text . "</option>";
		}
	}
}
