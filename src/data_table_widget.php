<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

/**
 * Piece of HTML displayed above or below the table
 */
interface IDataTableWidget {
	const placement_top = "top";
	const placement_bottom = "bottom";

	/**
	 * Returns HTML to display widget
	 *
	 * @param $form_name string Name of form
	 * @param $form_method string GET or POST
	 * @param $remote_url string
	 * @param $state DataFormState State which may contain widget state
	 * @return string HTML
	 */
	public function display($form_name, $form_method, $remote_url, $state);

	/**
	 * Describes where widget will be rendered relative to containing DataTable
	 *
	 * @return string See IDataTableWidget constants for possible values
	 */
	public function get_placement();
}

/**
 * Convert callback to IDataTableWidget. Defaults to placement_top
 */
class CallbackWidget implements IDataTableWidget {
	/**
	 * @var callable
	 */
	protected $callback;
	/**
	 * @var string
	 */
	protected $placement;

	/**
	 * @param $callback callable
	 * @param string $placement
	 * @throws Exception
	 */
	public function __construct($callback, $placement = IDataTableWidget::placement_top) {
		if (!is_callable($callback)) {
			throw new Exception("callback must be a callable");
		}
		if ($placement !== IDataTableWidget::placement_top &&
			$placement !== IDataTableWidget::placement_bottom) {
			throw new Exception("placement must be top or bottom");
		}

		$this->callback = $callback;
		$this->placement = $placement;
	}

	public function display($form_name, $form_method, $remote_url, $state)
	{
		$callback = $this->callback;
		return $callback($form_name, $form_method, $remote_url, $state);
	}

	public function get_placement()
	{
		return $this->placement;
	}
}

/**
 * Convenience class for displaying small pieces of HTML around a DataTable
 */
class CustomWidget implements IDataTableWidget {
	/**
	 * @var string HTML to display (unsanitized!)
	 */
	protected $html;
	/**
	 * @var string Where widget goes relative to DataTable
	 */
	protected $placement;

	/**
	 * @param string $html HTML to display
	 * @param string $placement Where widget goes relative to DataTable
	 * @throws Exception
	 */
	public function __construct($html, $placement=self::placement_top) {
		if (!is_string($html)) {
			throw new Exception("html must be a string");
		}
		if ($placement !== self::placement_top && $placement !== self::placement_bottom) {
			throw new Exception("placement must be 'top' or 'bottom'");
		}
		$this->html = $html;
		$this->placement = $placement;
	}

	/**
	 * Identical to constructor
	 *
	 * @param string $html HTML to display (unsanitized!)
	 * @param string $placement Where widget goes relative to DataTable
	 * @return CustomWidget
	 */
	public static function create($html, $placement=self::placement_top) {
		return new CustomWidget($html, $placement);
	}

	public function display($form_name, $form_method, $remote_url, $state)
	{
		return $this->html;
	}

	public function get_placement()
	{
		return $this->placement;
	}
}