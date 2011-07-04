<?php

if (isset($GLOBALS['wp_version']) && version_compare($GLOBALS['wp_version'], '2.5.1', '>')) :
	if (!function_exists('wp_generate_password')) :
		// wp_rand will come either from pre-2.6.3 or from pluggable.php
		function wp_generate_password($length = 12, $special_chars = true)
		{
			$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
			if ($special_chars) {
				$chars .= '!@#$%^&*()';
			}

			$password = '';
			for ($i=0; $i<$length; ++$i) {
				$password .= substr($chars, wp_rand(0, strlen($chars) - 1), 1);
			}

			return $password;
		}
	endif;
endif;

	/**
	 * Fights SQL truncation
	 */
	function icssf_sanitize_user_pre262($username)
	{
		return preg_replace('/\\s+/', ' ', $username);
	}

if (defined('ABSPATH')) :
	add_filter('sanitize_user', 'icssf_sanitize_user_pre262', 0);
endif;
