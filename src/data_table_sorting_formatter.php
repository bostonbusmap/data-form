<?php
interface IDataTableSortingFormatter {
	/**
	 * Render HTML for the sorting links on top
	 *
	 * TODO: figure out a way to simplify this interface, remove some parameters
	 *
	 * @param $form_name string
	 * @param $form_action string URL to refresh to
	 * @param $form_method string Either post or get
	 * @param $table_name string Name of table if table has a name
	 * @param $column_key string Which column search formatter applies to
	 * @param $state DataFormState State of form
	 * @param $default_value DataTableSortingState whatever the user filled in when the refresh happened
	 * @param $label string HTML to display for the label for this element. May be empty string for no label
	 * @return string
	 */
	function format($form_name, $form_action, $form_method, $table_name, $column_key, $state, $default_value, $label);

}

class DefaultSortingFormatter implements IDataTableSortingFormatter {

	function format($form_name, $form_action, $form_method, $table_name, $column_key, $state, $default_value, $label)
	{
		// draw sorting arrow and set hidden field

		$sorting_state_key = DataFormState::get_sorting_state_key($table_name);
		$sorting_state_column_key = array_merge($sorting_state_key, array($column_key));

		if ($state !== null && $state->has_item($sorting_state_column_key)) {
			$column_sorting_state = $state->find_item($sorting_state_column_key);
			$value = new DataTableSortingState(
				$column_sorting_state[DataTableSortingState::type_key],
				$column_sorting_state[DataTableSortingState::direction_key]
			);
		}
		elseif ($default_value !== null) {
			$value = $default_value;
		}
		else
		{
			$value = new DataTableSortingState(
				DataTableSortingState::sort_type_default,
				DataTableSortingState::sort_order_default
			);
		}

		$type_key = array_merge($sorting_state_column_key, array(DataTableSortingState::type_key));
		$direction_key = array_merge($sorting_state_column_key, array(DataTableSortingState::direction_key));

		$ret = DataTableHidden::display_hidden($form_name, $state, $type_key, $value->get_type());
		$ret .= DataTableHidden::display_hidden($form_name, $state, $direction_key, $value->get_direction(), "hidden_sorting");

		if ($value->get_direction() === DataTableSortingState::sort_order_asc) {
			$label_with_arrow = "&uarr; " . $label;
		}
		elseif ($value->get_direction() === DataTableSortingState::sort_order_desc) {
			$label_with_arrow = "&darr; " . $label;
		}
		else
		{
			$label_with_arrow = $label;
		}

		// write a link to sort in the opposite direction
		if ($value->get_direction() === DataTableSortingState::sort_order_asc)
		{
			$new_sorting_state = array(
				DataTableSortingState::type_key => $value->get_type(),
				DataTableSortingState::direction_key => DataTableSortingState::sort_order_desc
			);
		}
		else {
			$new_sorting_state = array(
				DataTableSortingState::type_key => $value->get_type(),
				DataTableSortingState::direction_key => DataTableSortingState::sort_order_asc
			);
		}

		$sorting_state_name = DataFormState::make_field_name($form_name, array_merge($sorting_state_key, array($column_key)));
		$onclick_obj = new DataTableBehaviorClearSortThenRefresh(array($sorting_state_name => $new_sorting_state));
		$ret .= DataTableLink::display_link($form_name, $form_method, "", $label_with_arrow,
			$onclick_obj, "");

		return $ret;
	}
}