<?php
interface IDataTableWidget {
	const placement_top = "top";
	const placement_bottom = "bottom";

	/**
	 * @param $form_name string Name of form
	 * @return string HTML
	 */
	public function display($form_name);

	public function get_placement();
}
