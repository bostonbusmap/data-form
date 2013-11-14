<?php
/**
 * Rule to validate a form given its state
 */
interface IValidatorRule {
	/**
	 * @param $form DataForm
	 * @param $state DataFormState
	 * @return string Text of the error, or an empty string if no error
	 */
	function validate($form, $state);
}