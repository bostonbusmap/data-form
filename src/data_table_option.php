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
	protected $default_selected;

	public function __construct($text, $value, $default_selected=false) {
		$this->text = $text;
		if (is_int($value)) {
			$value = (string)$value;
		}
		$this->value = $value;
		$this->default_selected = $default_selected;
	}

	public function get_text() {
		return $this->text;
	}

	public function get_value() {
		return $this->value;
	}

	public function is_default_selected() {
		return $this->default_selected;
	}

	/**
	 * @param $selected bool
	 * @return string HTML
	 */
	public function display($selected) {
		$value = $this->value;
		$text = $this->text;

		if ($selected) {
			return '<option value="' . htmlspecialchars($value) . '" selected>' . $text . "</option>";
		}
		else
		{
			return '<option value="' . htmlspecialchars($value) . '">' . $text . "</option>";
		}
	}
}
