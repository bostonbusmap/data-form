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
		if ($this->extra_params) {
			$params = " + \"&" . $this->extra_params . "\"";
		}
		else
		{
			$params = "";
		}
		return "$.get(\"" . $form_action . "\", $(this).parents(\"form\").serialize() $params, function(data, textStatus, jqXHR) { $(\"#" .
			$form_name . "\").html(data);})";
	}
}
class DataTableBehaviorDefault implements IDataTableBehavior {
	function action($form_name, $form_action) {
		return "";
	}
}