<?php
/**
 * Provides helper functions.
 *
 * @since      0.1.0
 *
 * @package    PyIS_Address_Collection
 * @subpackage PyIS_Address_Collection/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since 0.1.0
 *
 * @return PyIS_Address_Collection
 */
function PYISADDRESSCOLLECTION() {
	return PyIS_Address_Collection::instance();
}