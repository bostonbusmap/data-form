<?php
interface IDataTableBehavior {
	/**
	 * @param string $form_name Name of form
	 * @param string $form_action URL to submit to or refresh from
	 * @param string $form_method Method of form, either GET or POST. Should be same as what form is declared as in HTML
	 * @return string Javascript to execute when submit button is clicked or select item is changed
	 */
	function action($form_name, $form_action, $form_method);
}

class DataTableBehaviorNone implements IDataTableBehavior {
	function action($form_name, $form_action, $form_method) {
		return "return false;";
	}
}
class DataTableBehaviorSubmitNewWindow implements IDataTableBehavior {
	function action($form_name, $form_action, $form_method) {
		return "$(this).parents(\"form\").attr(\"action\", \"$form_action\");$(this).parent(\"form\").attr(\"target\", \"_blank\");";
	}
}
class DataTableBehaviorSubmit implements IDataTableBehavior {
	function action($form_name, $form_action, $form_method) {
		return "$(this).parents(\"form\").attr(\"action\", \"$form_action\");";
	}
}
class DataTableBehaviorRefresh implements IDataTableBehavior {
	/** @var string  */
	protected $extra_params;
	public function __construct($extra_params="") {
		$this->extra_params = $extra_params;
	}
	function action($form_name, $form_action, $form_method) {
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

		$method = strtolower($form_method);
		if ($method != "post" && $method != "get") {
			throw new Exception("Unknown method '$method");
		}

		return "$." . $method . "(\"" . $form_action . "\", $(this).parents(\"form\").serialize()  + \"$params\", function(data, textStatus, jqXHR) { $(\"#" .
			$form_name . "\").html(data);});return false;";
	}
}
class DataTableBehaviorClearSortThenRefresh implements IDataTableBehavior {
	/** @var $extra_params string */
	protected $extra_params;
	public function __construct($extra_params) {
		$this->extra_params = $extra_params;
	}

	function action($form_name, $form_action, $form_method) {

		$clear_sorts = "$(this).parents(\"form\").find(\".hidden_sorting\").attr(\"value\", \"\");";
		$refresh_behavior = new DataTableBehaviorRefresh($this->extra_params);
		return $clear_sorts . $refresh_behavior->action($form_name, $form_action, $form_method);
	}
}
class DataTableBehaviorDefault implements IDataTableBehavior {
	function action($form_name, $form_action, $form_method) {
		return "";
	}
}
class DataTableBehaviorCustom implements IDataTableBehavior {
	/** @var  string */
	protected $javascript;
	public function __construct($javascript) {
		$this->javascript = $javascript;
	}
	function action($form_name, $form_action, $form_method) {
		return $this->javascript;
	}
}