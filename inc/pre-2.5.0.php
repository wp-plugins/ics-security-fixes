<?php

	/**
	 * CVE-2008-4769
	 */
	function icssf_request_pre250($vars)
	{
		if (is_array($vars)) {
			if (isset($vars['cat'])) {
				$vars['cat'] = abs(intval($vars['cat']));
			}
		}

		return $vars;
	}

// Fix Wordpress 2.5 Cookie Integrity Protection Vulnerability
if (!function_exists('wp_validate_auth_cookie') && !function_exists('wp_generate_auth_cookie')) :
	function wp_validate_auth_cookie($cookie = '')
	{
		if (empty($cookie)) {
			if (empty($_COOKIE[AUTH_COOKIE])) {
				return false;
			}

			$cookie = $_COOKIE[AUTH_COOKIE];
		}

		$cookie_elements = explode('|', $cookie);
		if (count($cookie_elements) != 3) {
			return false;
		}

		list($username, $expiration, $hmac) = $cookie_elements;

		$expired = $expiration;

		if (defined('DOING_AJAX') || 'POST' == $_SERVER['REQUEST_METHOD']) {
			$expired += 3600;
		}

		if ($expired < time()) {
			return false;
		}

		$key  = wp_hash($username . '|' . $expiration);
		$hash = hash_hmac('md5', $username . '|' . $expiration, $key);

		if ($hmac != $hash) {
			return false;
		}

		$user = get_userdatabylogin($username);
		if (!$user) {
			return false;
		}

		return $user->ID;
	}

	function wp_generate_auth_cookie($user_id, $expiration)
	{
		$user   = get_userdata($user_id);
		$key    = wp_hash($user->user_login . '|' . $expiration);
		$hash   = hash_hmac('md5', $user->user_login . '|' . $expiration, $key);
		$cookie = $user->user_login . '|' . $expiration . '|' . $hash;
		return apply_filters('auth_cookie', $cookie, $user_id, $expiration);
	}

endif;

if (!function_exists('wp_hash')) :
	function wp_hash($data)
	{
		return hash_hmac('md5', $data, wp_salt());
	}
endif;

if (!function_exists('like_escape')) :
	function like_escape($text)
	{
		return str_replace(array("%", "_"), array("\\%", "\\_"), $text);
	}
endif;

if (defined('ABSPATH')) :
	add_filter('request', 'icssf_request_pre250');
endif;
