<?php

/**
 * @author Stephan Gambke
 * @author Sam Wilson
 * @author Amr El-Absy
 * @file
 * @ingroup PageForms
 */

/**
 * @ingroup PageForms
 */

use MediaWiki\Widget\DateTimeInputWidget;

class PFDateTimePicker extends PFFormInput {

	/**
	 * @param string $input_number The number of the input in the form.
	 * @param string $cur_value The current value of the input field.
	 * @param string $input_name The name of the input.
	 * @param bool $disabled Is this input disabled?
	 * @param array $other_args An associative array of other parameters that were present in the
	 *  input definition.
	 */
	public function __construct( $input_number, $cur_value, $input_name, $disabled, array $other_args ) {
		if ( $cur_value == 'now' ) {
			$cur_value = date( 'Y/m/d H:i' ); // include hours and minutes
		}
		parent::__construct( $input_number, $cur_value, $input_name, $disabled, $other_args );
	}

	/**
	 * Returns the name of the input type this class handles: menuselect.
	 *
	 * This is the name to be used in the field definition for the "input
	 * type" parameter.
	 *
	 * @return String The name of the input type this class handles.
	 */
	public static function getName() {
		return 'datetimepicker';
	}

	/**
	 * Returns the HTML code to be included in the output page for this input.
	 *
	 * Ideally this HTML code should provide a basic functionality even if the
	 * browser is not JavaScript capable. I.e. even without JavaScript the user
	 * should be able to input values.
	 * @return string
	 */
	public function getHtmlText() {
		$widget = new DateTimeInputWidget( [
			'type' => 'datetime',
			'name' => $this->mInputName,
			'value' => $this->mCurrentValue,
			'id' => 'input_' . $this->mInputNumber,
			'classes' => [ 'pfDateTimePicker' ],
			'infusable' => true,
		] );
		return $widget->toString();
	}

	/**
	 * Returns the set of SMW property types which this input can
	 * handle, but for which it isn't the default input.
	 * @return string[]
	 */
	public static function getOtherPropTypesHandled() {
		return [ '_str', '_dat' ];
	}

	/**
	 * Returns the set of parameters for this form input.
	 * @return array[]
	 */
	public static function getParameters() {
		$params = array_merge(
			parent::getParameters(),
			PFDatePickerInput::getParameters()
		);

		$params['mintime'] = [
			'name' => 'mintime',
			'type' => 'string',
			'description' => wfMessage( 'pageforms-timepicker-mintime' )->text(),
		];
		$params['maxtime'] = [
			'name' => 'maxtime',
			'type' => 'string',
			'description' => wfMessage( 'pageforms-timepicker-maxtime' )->text(),
		];
		$params['interval'] = [
			'name' => 'interval',
			'type' => 'int',
			'description' => wfMessage( 'pageforms-timepicker-interval' )->text(),
		];

		return $params;
	}

	/**
	 * Returns the names of the resource modules this input type uses.
	 *
	 * Returns the names of the modules as an array or - if there is only one
	 * module - as a string.
	 *
	 * @return null|string|array
	 */
	public function getResourceModuleNames() {
		return [ 'ext.pageforms.datetimepicker' ];
	}

}
