<?php

// TODO: put most of this into a JavaScript file for cleanliness

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
		return 'return DataForm.setParamsThenSubmit(this, event, ' . json_encode($form_action) . ', ' . json_encode($this->params) . ');';
	}
}
class DataTableBehaviorSubmit implements IDataTableBehavior {
	function action($form_name, $form_action, $form_method) {
		if (!$form_action) {
			throw new Exception("form_action is empty");
		}
		return 'return DataForm.submit(this, event, ' . json_encode($form_action) . ');';
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
		$params = array($validate_name => "true");

		$method = strtolower($form_method);
		if ($method != "post" && $method != "get") {
			throw new Exception("Unknown method '$method'");
		}
		$flash_name = $form_name . "_flash";

		// first submit data with validation parameter to validation url
		// If a non-empty result is received (which would be errors), put it in flash div,
		// else do the submit
		return 'return DataForm.submitThenValidate(this, event, ' . json_encode($form_action) . ', ' . json_encode($method) .
			', ' . json_encode($this->validation_url) . ', ' . json_encode($flash_name) . ', ' . json_encode($params) . ');';
	}
}
class DataTableBehaviorRefresh implements IDataTableBehavior {
	/** @var array */
	protected $extra_params;
	public function __construct($extra_params=array()) {
		if (!is_array($extra_params)) {
			throw new Exception("params must be in an array");
		}
		foreach ($extra_params as $k => $v) {
			if (!is_string($k) || trim($k) === "") {
				throw new Exception("Each key in extra_params must be a non-empty string");
			}
		}
		$this->extra_params = $extra_params;
	}
	function action($form_name, $form_action, $form_method) {
		$only_display_form_name = DataFormState::make_field_name($form_name, DataFormState::only_display_form_key());
		$params = $this->extra_params;
		$params[$only_display_form_name] = "true";

		$method = strtolower($form_method);
		if ($method != "post" && $method != "get") {
			throw new Exception("Unknown method '$method'");
		}

		return 'return DataForm.refresh(this, event, ' . json_encode($form_action) . ', ' . json_encode($form_method) . ', ' .
			json_encode($form_name) . ', ' . json_encode($params) . ');';
	}
}

class DataTableBehaviorRefreshImage implements IDataTableBehavior {
	/** @var array */
	protected $extra_params;
	/**
	 * @var string The name of the div to refresh with data. If falsey the form's div will be refreshed
	 */
	protected $div;
	/**
	 * @var string The name of the div which overlays the other div with some loading animation
	 */
	protected $div_overlay;

	const height_key = "_height";
	const width_key = "_width";

	public function __construct($div, $div_overlay, $extra_params=array()) {
		if (!is_array($extra_params)) {
			throw new Exception("params must be in an array");
		}
		if (!is_string($div) || trim($div) === "") {
			throw new Exception("div id must be a non-empty string");
		}
		if (!is_string($div_overlay) || trim($div_overlay) === "") {
			throw new Exception("div_overlay must be a non-empty string");
		}
		foreach ($extra_params as $k => $v) {
			if (!is_string($k) || trim($k) === "") {
				throw new Exception("Each key in extra_params must be a non-empty string");
			}
		}
		$this->extra_params = $extra_params;
		$this->div = $div;
		$this->div_overlay = $div_overlay;
	}

	function action($form_name, $form_action, $form_method)
	{
		$only_display_form_name = DataFormState::make_field_name($form_name, DataFormState::only_display_form_key());
		$params = $this->extra_params;
		$params[$only_display_form_name] = "true";

		if ($this->div) {
			$div = $this->div;
		}
		else
		{
			$div = $form_name;
		}

		$height_name = DataFormState::make_field_name($form_name, array(self::height_key));
		$width_name = DataFormState::make_field_name($form_name, array(self::width_key));

		return 'return DataForm.refreshImage(this, event, ' . json_encode($form_action) . ', ' . json_encode($form_method) . ', ' .
		json_encode($div) . ', ' . json_encode($this->div_overlay) . ', ' .
		json_encode($height_name) . ', ' . json_encode($width_name) . ', ' .
		json_encode($params) . ');';
	}
}
class DataTableBehaviorClearSortThenRefresh implements IDataTableBehavior {
	/** @var $extra_params array */
	protected $extra_params;
	public function __construct($extra_params) {
		$this->extra_params = $extra_params;
	}

	function action($form_name, $form_action, $form_method) {
		$only_display_form_name = DataFormState::make_field_name($form_name, DataFormState::only_display_form_key());
		$params = $this->extra_params;
		$params[$only_display_form_name] = "true";

		$method = strtolower($form_method);
		if ($method != "post" && $method != "get") {
			throw new Exception("Unknown method '$method'");
		}

		return 'return DataForm.clearSortThenRefresh(this, event, ' . json_encode($form_action) . ', ' . json_encode($form_method) . ', ' .
		json_encode($form_name) . ', ' . json_encode($params) . ');';

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