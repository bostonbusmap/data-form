<?php
/**
 * Some piece of HTML displayed right outside the table
 */
interface IDataTableWidget {
	const placement_top = "top";
	const placement_bottom = "bottom";

	/**
	 * @param $form_name string Name of form
	 * @param $form_method string GET or POST
	 * @param $state DataFormState
	 * @return string HTML
	 */
	public function display($form_name, $form_method, $state);

	public function get_placement();
}
