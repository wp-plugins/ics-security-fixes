<?php

	/**
	 * Funny Pings, Part 1
	 */
	function icssf_add_ping_pre252($new)
	{
		global $wpdb;
		return $wpdb->escape($new);
	}

if ($GLOBALS['wp_version'] == '2.5.1') :
	if (!function_exists('wp_generate_password')) :
		/**
		 * Fix password reset bug
		 */
		function wp_generate_password()
		{
			$chars  = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
			$length = 10;
			$password = '';
			if (function_exists('wp_rand')) {
				for ($i = 0; $i < $length; $i++) {
					$password .= substr($chars, wp_rand(0, 61), 1);
				}
			}
			else {
				for ($i = 0; $i < $length; $i++) {
					$password .= substr($chars, mt_rand(0, 61), 1);
				}
			}

			return $password;
		}
	endif;
endif;

if (defined('ABSPATH')) :
	add_filter('add_ping', 'icssf_add_ping_pre252');
endif;