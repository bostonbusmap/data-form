<?php
interface IDataTableWidget {
	const placement_top = "top";
	const placement_bottom = "bottom";

	/**
	 * @param $form_name string Name of form
	 * @param $state DataFormState
	 * @return string HTML
	 */
	public function display($form_name, $state);

	public function get_placement();
}
