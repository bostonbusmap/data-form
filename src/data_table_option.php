<?php
/**
 * Represents HTML option element. Provide an array of this object to DataTableOptions
 */
class DataTableOption {
	/** @var  string */
	protected $text;
	/** @var  string */
	protected $value;
	/** @var  bool Selected by default? */
	protected $default_selected;

	/**
	 * @param $text string Text for option in menu (unsanitized HTML)
	 * @param $value string Value for option in menu
	 * @param bool $default_selected Is selected by default?
	 */
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
	 * Render HTML option element. Use inside DataTableOptions which will render inside <select>
	 *
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
