<?php
/**
 * Validation example using DataForm
 *
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";

/**
 * Validator rule which errors if there are spaces in textbox
 */
class NoSpacesInTextbox implements IValidatorRule {
	protected $name;
	public function __construct($name) {
		$this->name = $name;
	}

	function validate($form, $state)
	{
		$data = $state->find_item(array($this->name));
		if (!is_string($data)) {
			// exceptions thrown during validation are meant to be exceptional
			// in this case, we should always be getting a string here, it isn't just a validation error
			throw new Exception("Validating item which isn't text");
		}

		if (strpos($data, " ") !== false) {
			return "Remove spaces from field " . $this->name . " before proceeding";
		}
		return "";
	}
}

/**
 * Validator rule which errors if even numbers are detected
 */
class OnlyEvenItemsSelected implements IValidatorRule {
	protected $prefix;
	public function __construct($name) {
		$this->name = $name;
	}

	function validate($form, $state) {
		$selected_items = $state->find_item(array($this->name));
		if (!$selected_items) {
			// no numbers selected at all
			return "";
		}
		if (!is_array($selected_items)) {
			throw new Exception("Item " . $this->name . " was expected to be an array");
		}
		$odds = array();
		foreach ($selected_items as $item) {
			if ($item === "") {
				continue;
			}
			if (!is_numeric($item)) {
				throw new Exception("Assumed all items were numeric");
			}
			$number = (int)$item;
			if ($number % 2 != 0) {
				//is odd
				$odds[] = $number;
			}
		}
		if ($odds) {
			return "Found odd numbers selected: " . join(", ", $odds);
		}
		else
		{
			return "";
		}
	}
}

/**
 * Make DataForm for validation example
 *
 * @param DataFormState $state
 * @return DataForm
 */
function make_form($state) {
	$this_url = $_SERVER['REQUEST_URI'];

	// Two columns: checkbox and number (both using same data, column_key = 'number')
	$columns = array();
	$columns[] = DataTableColumnBuilder::create()->cell_formatter(new DataTableCheckboxCellFormatter())->column_key("number")->build();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Numbers")->column_key("number")->build();

	// make 15 rows of numbers
	$rows = array();
	for ($i = 0; $i < 15; $i++) {
		$row = array();

		if ($i == 5) {
			// The Selected object is a special wrapper object meaning selected by default
			$row["number"] = new Selected($i, true);
		}
		else
		{
			$row["number"] = $i;
		}

		$rows[] = $row;
	}

	// Two widgets: a button that validates the form then submits, and a textbox which will be validated
	$widgets = array();
	$widgets[] = DataTableButtonBuilder::create()->text("Validate and submit")->form_action("validation_submit.php")->behavior(new DataTableBehaviorSubmitAndValidate($this_url))->build();
	$widgets[] = DataTableTextboxBuilder::create()->text("Enter text without spaces")->name("text")->build();

	// Add validator rules
	$validator_rules = array();
	$validator_rules[] = new NoSpacesInTextbox("text");
	$validator_rules[] = new OnlyEvenItemsSelected("number");

	// create DataTable and DataForm, putting validator rules in validator_rules parameter
	$table = DataTableBuilder::create()->columns($columns)->rows($rows)->widgets($widgets)->build();
	$form = DataFormBuilder::create($state->get_form_name())->tables(array($table))->
		validator_rules($validator_rules)->remote($this_url)->build();
	return $form;
}

try {
	$state = new DataFormState("select_3", $_POST);
	$form = make_form($state);
	if ($state->only_display_form()) {
		// DataFormState indicates that only form HTML should be displayed
		echo $form->display_form($state);
	}
	elseif ($state->only_validate()) {
		// DataFormState indicates that only validation error HTML should be displayed
		// this goes in a div named form_name_flash
		echo $form->validate($state);
	}
	else
	{
		gfy_header("Select 3", "");
		echo "Unselect all odd numbers and remove spaces from textbox<br />";
		echo $form->display($state);
	}
}
catch (Exception $e) {
	echo "<pre>" . $e . "</pre>";
}