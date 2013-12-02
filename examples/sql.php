<?php
require_once "sql_lib.php";

try {
	$state = new DataFormState("searches", $_POST);
	$form = make_searches_form($state);
	if ($state->only_display_form()) {
		echo $form->display_form($state);
	}
	else
	{
		gfy_header("SQL example", "");
		echo $form->display($state);
	}
}
catch (Exception $e) {
	echo "<pre>" . $e . "</pre>";
}