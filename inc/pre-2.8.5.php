<?php

if (!empty($GLOBALS['wp_version']) && version_compare($GLOBALS['wp_version'], '2.7', '>=')) :
	if (!function_exists('get_userdata')) :
		/**
		 * Wordpress 2.7.x/2.8.x admin remote code execution exploit
		 */
		function get_userdata($user_id)
		{
			global $wpdb;
			$user_id = abs(intval($user_id));
			if ($user_id < 1) {
				return false;
			}

			$user = wp_cache_get($user_id, 'users');
			if ($user) {
				$user->display_name = str_replace(array('\\', '"', "'", '}', ';'), ' ', $user->display_name);
				return $user;
			}

			if (!$user = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->users WHERE ID = %d LIMIT 1", $user_id))) {
				return false;
			}

			$user->display_name = str_replace(array('\\', '"', "'", '}', ';'), ' ', $user->display_name);
			_fill_user($user);
			return $user;
		}
	endif;
endif;
