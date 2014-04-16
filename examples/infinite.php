<?php

require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";

/**
 * Stream of numbers x^y where x is incremented each loop and y is a given floating point value.
 */
class ExponentIterator implements Iterator {

	/**
	 * @var int Internal counter starting from zero
	 */
	protected $counter;
	/**
	 * @var int This plus $counter is the base of the exponent
	 */
	protected $start;

	/**
	 * @var float The exponent
	 */
	protected $exponent;
	/**
	 * @var int Maximum number of rows to iterate through
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
 * @throws Exception
 */
function make_form($state) {
	if (!($state instanceof DataFormState)) {
		throw new Exception("state expected to be instance of DataFormState");
	}
	$exponent = 1.5;

	// Create a couple of columns for the base and exponent
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

	// Add a refresh button
	$widgets = array();
	$widgets[] = DataTableButtonBuilder::create()
		->text("Refresh")
		->behavior(new DataTableBehaviorRefresh())
		->build();

	// Figure out pagination from default $settings
	// and user provided $state
	$pagination_info = DataFormState::make_pagination_info($state, $settings);

	if ($pagination_info->get_limit() === 0) {
		// A limit of zero means all the rows, but
		// we have infinite rows
		throw new Exception("Cannot view all rows of infinite data");
	}

	// Create iterator over piece of number line we want to consider
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