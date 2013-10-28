<?php
interface IDataTableBehavior {
	/**
	 * @param string $form_name Name of form
	 * @param string $form_action URL to submit to or refresh from
	 * @return string Javascript to execute when submit button is clicked or select item is changed
	 */
	function action($form_name, $form_action);
}

class DataTableBehaviorNone implements IDataTableBehavior {
	function action($form_name, $form_action) {
		return "return false;";
	}
}
class DataTableBehaviorSubmitNewWindow implements IDataTableBehavior {
	function action($form_name, $form_action) {
		return "$(this).parent(\"form\").attr(\"action\", \"$form_action\");$(this).parent(\"form\").attr(\"target\", \"_blank\");";
	}
}
class DataTableBehaviorSubmit implements IDataTableBehavior {
	function action($form_name, $form_action) {
		return "$(this).parent(\"form\").attr(\"action\", \"$form_action\");";
	}
}
class DataTableBehaviorRefresh implements IDataTableBehavior {
	/** @var string  */
	protected $extra_params;
	public function __construct($extra_params="") {
		$this->extra_params = $extra_params;
	}
	function action($form_name, $form_action) {
		$only_display_form_name = DataFormState::make_field_name($form_name, DataFormState::only_display_form_key());
		$params = "&" . $only_display_form_name . "=true";

		if ($this->extra_params) {
			if (substr($this->extra_params, 0, 1) == "&") {
				$extra_params = substr($this->extra_params, 1);
			}
			else
			{
				$extra_params = $this->extra_params;
			}
			$params .= "&" . $extra_params;
		}

		return "$.post(\"" . $form_action . "\", $(this).parents(\"form\").serialize()  + \"$params\", function(data, textStatus, jqXHR) { $(\"#" .
			$form_name . "\").html(data);});return false;";
	}
}
class DataTableBehaviorDefault implements IDataTableBehavior {
	function action($form_name, $form_action) {
		return "";
	}
}