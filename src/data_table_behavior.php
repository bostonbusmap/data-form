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

class DataTableBehaviorSetParamsThenSubmit implements IDataTableBehavior {
	/** @var array */
	protected $params;
	public function __construct($params) {
		if (!is_array($params)) {
			throw new Exception("params must be array");
		}
		$this->params = $params;
	}
	function action($form_name, $form_action, $form_method) {
		if (!$form_action) {
			throw new Exception("form_action is empty");
		}
		$params_js = "";
		foreach ($this->params as $key => $value) {
			$param_js = '$(' . json_encode("#" . jquery_escape($key)) . ').attr("value", ' . json_encode($value) . ");";
			$params_js .= $param_js;
		}
		return 'var $form=$(this).parents("form"); $form.attr("action", ' . json_encode($form_action) . ');' . $params_js . '$form.submit();return false;';
	}
}
class DataTableBehaviorSubmit implements IDataTableBehavior {
	function action($form_name, $form_action, $form_method) {
		if (!$form_action) {
			throw new Exception("form_action is empty");
		}
		return '$form=$(this).parents("form"); $form.attr("action", ' . json_encode($form_action) . ');$form.submit();return false;';
	}
}
class DataTableBehaviorSubmitAndValidate implements IDataTableBehavior {
	/** @var  string */
	protected $validation_url;
	public function __construct($validation_url) {
		if (!$validation_url || !is_string($validation_url)) {
			throw new Exception("validation_url must be a non-empty string");
		}
		$this->validation_url = $validation_url;
	}

	function action($form_name, $form_action, $form_method)
	{
		$validate_name = DataFormState::make_field_name($form_name, DataFormState::only_validate_key());
		$params = "&" . $validate_name . "=true";

		$method = strtolower($form_method);
		if ($method != "post" && $method != "get") {
			throw new Exception("Unknown method '$method'");
		}
		$flash_name = $form_name . "_flash";

		// first submit data with validation parameter to validation url
		// If a non-empty result is received (which would be errors), put it in flash div,
		// else do the submit
		return 'var $form=$(this).parents("form"); $.' . $method . '(' . json_encode($this->validation_url) . ','.
			' $form.serialize()  + ' .
			json_encode($params) .
			', function(data, textStatus, jqXHR) { ' .
			'if (data) { $(' . json_encode("#" . $flash_name) . ').html(data); } else {' .
			'$form.attr("action", ' .
			json_encode($form_action) .
			');$form.submit();' .
			'}}); return false;';
	}
}
class DataTableBehaviorRefresh implements IDataTableBehavior {
	/** @var array */
	protected $extra_params;
	/**
	 * @var string The name of the div to refresh with data. If falsey the form's div will be refreshed
	 */
	protected $div;
	public function __construct($extra_params=array(), $div="") {
		if (!is_array($extra_params)) {
			throw new Exception("params must be in an array");
		}
		if (!is_string($div)) {
			throw new Exception("div id must be string");
		}
		$this->extra_params = $extra_params;
		$this->div = $div;
	}
	function action($form_name, $form_action, $form_method) {
		$only_display_form_name = DataFormState::make_field_name($form_name, DataFormState::only_display_form_key());
		$params = "&" . $only_display_form_name . "=true";

		foreach ($this->extra_params as $k => $v) {
			$params .= "&" . urlencode($k) . "=" . urlencode($v);
		}

		$method = strtolower($form_method);
		if ($method != "post" && $method != "get") {
			throw new Exception("Unknown method '$method'");
		}

		if ($this->div) {
			$div = $this->div;
		}
		else
		{
			$div = $form_name;
		}

		// to submit the form as AJAX we need to serialize the form to json and put it in the parameter string
		// the second part of this takes that result and puts it in the div with the same name as the form
		// ie, replace the form with a refreshed copy
		return 'var $form=$(this).parents("form"); $.' . $method . '(' . json_encode($form_action) .
			', $form.serialize()  + ' . json_encode($params) .
			', function(data, textStatus, jqXHR) { $(' .
			json_encode("#" . $div) .
			').html(data);});return false;';
	}
}
class DataTableBehaviorClearSortThenRefresh implements IDataTableBehavior {
	/** @var $extra_params array */
	protected $extra_params;
	public function __construct($extra_params) {
		$this->extra_params = $extra_params;
	}

	function action($form_name, $form_action, $form_method) {

		$clear_sorts = '$(this).parents("form").find(".hidden_sorting").attr("value", "");';
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