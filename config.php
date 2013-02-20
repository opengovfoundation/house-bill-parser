<?php
/**
 * 	Configuration file for bill parser script
 */
	
	/**
	 * 	Database Configuration
	 */
	
	define('DB_HOST', '');
	define('DB_NAME', '');
	define('DB_USER', '');
	define('DB_PASS', '');
	
	/**
	 * 	Root tag for bill content
	 */
	define('ROOT_TAG', 'legis-body');
	
	/**
	 * 	Include necessary files
	 */
	require_once('functions.php');
	require_once('bill.class.php');