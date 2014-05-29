<?php

require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";

/**
 * @param $state DataFormState
 * @return DataForm
 */
function make_form($state) {
	$columns = array();
	$columns[] = DataTableColumnBuilder::create()
		->column_key("row")
		->display_header_name("Row")
		->build();

	$columns[] = DataTableColumnBuilder::create()
		->css_class("class-red")
		->column_key("red")
		->display_header_name("Red")
		->build();
	$columns[] = DataTableColumnBuilder::create()
		->css_class("class-green")
		->column_key("green")
		->display_header_name("Green")
		->build();
	$columns[] = DataTableColumnBuilder::create()
		->css_class("class-blue")
		->column_key("blue")
		->display_header_name("Blue")
		->build();

	$field_names = array("row", "red", "green", "blue");

	$rows = array();
	$rows[1] = array(1, 1,2,3);
	$rows[2] = array(2, 4,5,6);
	$rows[3] = array(3, 7,8,9);

	$table = DataTableBuilder::create()
		->columns($columns)
		->rows($rows)
		->row_classes(array("2" => "class-blue"))
		->field_names($field_names)
		->build();

	return DataFormBuilder::create($state->get_form_name())
		->method("GET")
		->tables(array($table))
		->remote("highlighting.php")
		->build();
}

$state = new DataFormState("highlighting", $_GET);
if ($state->only_display_form()) {
	try {
		$form = make_form($state);
		echo $form->display_form($state);
	} catch (Exception $e) {
		echo json_encode(array("error" => $e->getMessage()));
	}
} else {
	gfy_header("", "");

	echo "<style>
	.data-form .class-red { background-color: rgba(255, 0, 0, 0.5); }
	.data-form .class-green { background-color: rgba(0, 255, 0, 0.5); }
	.data-form .class-blue { background-color: rgba(0, 0, 255, 0.5); }

	</style>";

	try {
		$form = make_form($state);
		echo $form->display($state);
	} catch (Exception $e) {
		echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
	}
	gfy_footer();
}

