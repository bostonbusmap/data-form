<?php

require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";

/**
 * Infinite stream of numbers x^y where x is incremented each loop and y is a given floating point value
 */
class ExponentIterator implements Iterator {

	/**
	 * @var int
	 */
	protected $counter;
	/**
	 * @var int
	 */
	protected $start;

	/**
	 * @var float
	 */
	protected $exponent;
	/**
	 * @var int
	 */
	protected $limit;

	/**
	 * @param $start int
	 * @param $exponent float|int
	 * @param $limit int
	 * @throws Exception
	 */
	public function __construct($start, $exponent, $limit) {
		if (!is_int($start)) {
			throw new Exception("starting point must be an integer");
		}
		if (!is_numeric($exponent)) {
			throw new Exception("exponent must be a number");
		}
		if (!is_int($limit)) {
			throw new Exception("limit must be an integer");
		}
		$exponent = (float)$exponent;
		$this->start = $start;
		$this->counter = 0;
		$this->exponent = $exponent;
		$this->limit = $limit;
	}

	public function current()
	{
		$index = $this->counter + $this->start;
		return array("index" => $index,
			"value" => pow($index, $this->exponent));
	}

	public function next()
	{
		$this->counter++;
	}

	public function key()
	{
		$index = $this->counter + $this->start;
		return $index;
	}

	public function valid()
	{
		return $this->counter < $this->limit;
	}

	public function rewind()
	{
		$this->counter = 0;
	}
}

/**
 * @param $state DataFormState
 * @return DataForm
 */
function make_form($state) {
	$exponent = 1.5;

	$columns = array(
		DataTableColumnBuilder::create()
			->display_header_name("x")
			->column_key("index")
			->build(),
		DataTableColumnBuilder::create()
			->display_header_name("x^" . $exponent)
			->column_key("value")
			->build()
	);

	$widgets = array();
	$widgets[] = DataTableButtonBuilder::create()
		->text("Refresh")
		->behavior(new DataTableBehaviorRefresh())
		->build();

	$pagination_info = DataFormState::make_pagination_info($state, $settings);
	$rows = new ExponentIterator($pagination_info->get_offset(), $exponent, $pagination_info->get_limit());

	$table = DataTableBuilder::create()
		->columns($columns)
		->widgets($widgets)
		->rows($rows)
		->settings($settings)
		->build();

	return DataFormBuilder::create($state->get_form_name())
		->tables(array($table))
		->remote("infinite.php")
		->build();
}

try
{
	$state = new DataFormState("infinite", $_GET);
	if ($state->only_display_form()) {
		try {
			$form = make_form($state);
			echo $form->display_form($state);
		}
		catch (Exception $e) {
			echo json_encode(array("error" => $e->getMessage()));
		}
	}
	else
	{
		gfy_header("Infinite pagination", "");
		$form = make_form($state);
		echo $form->display($state);
	}
}
catch (Exception $e) {
	echo "<pre>" . $e->getMessage() . "</pre>";
}