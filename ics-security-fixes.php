<?php
/*
Plugin Name: ICS Security Fixes
Plugin URI: http://blog.sjinks.pro/wordpress-plugins/ics-security-fixes/
Description: Tries to fix vulnerabilities in outdated WordPress installations
Version: 0.4
Author: ICS
Author URI: http://blog.sjinks.pro/
License: BSD
*/

	defined('ABSPATH') or die();
	$GLOBALS['ics_security_fixes_active'] = 0;

	class ICS_Security_Fixes
	{
		public static function instance()
		{
			static $self = null;
			if (!$self) {
				$self = new ICS_Security_Fixes();
			}

			return $self;
		}

		public function __construct()
		{
			global $wp_version;

			register_activation_hook(__FILE__,   array($this, 'activate_plugin'));
			register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));

			if (version_compare($wp_version, '3.0.2', '<')) {
				add_filter('query',       array($this, 'query'));
				add_filter('get_to_ping', array($this, 'get_to_ping'));
			}

			if (version_compare($wp_version, '2.5.1', '<=')) {
				add_filter('add_ping', array($this, 'add_ping'));
			}

			if (version_compare($wp_version, '2.5', '<=')) {
				add_filter('request', array($this, 'request'));
			}

			if (version_compare($wp_version, '2.5', '>=') && version_compare($wp_version, '2.6.5', '<=')) {
				// Want to sleep and don't want to go through the horrors of dashboard API
				add_filter('dashboard_primary_feed',   array($this, 'dashboard_primary_feed'), 1000);
				add_filter('dashboard_secondary_feed', array($this, 'dashboard_secondary_feed'), 1000);
			}

			if (version_compare($wp_version, '2.6.2', '<')) {
				add_filter('sanitize_user', array($this, 'sanitize_user'), 0);
			}

			if (version_compare($wp_version, '3.0', '<')) {
				add_filter('preprocess_comment', array($this, 'preprocess_comment'));
			}

			if (version_compare($wp_version, '2.3', '<')) {
				add_filter('post_mime_type_pre', array($this, 'post_mime_type_pre'));
			}

			if (version_compare($wp_version, '3.0.5', '<')) {
				if (function_exists('wp_kses_data')) {
					add_filter('comment_text', 'wp_kses_data');
				}

				if (function_exists('sanitize_key')) {
					foreach (array('pre_post_status', 'pre_post_comment_status', 'pre_post_ping_status') as $filter) {
						add_filter($filter, 'sanitize_key');
					}
				}
			}

			add_action('http_api_curl', array($this, 'http_api_curl'));

			remove_action('wp_head', 'wp_generator');
			foreach (array('rss2_head', 'commentsrss2_head', 'rss_head', 'rdf_header', 'atom_head', 'comments_atom_head', 'opml_head', 'app_head') as $action) {
				remove_action($action, 'the_generator');
			}

			// Replace Inavlid username and Invalid password messages with a generic one
			add_filter('authenticate', array($this, 'authenticate'), 1000, 3);

			if (!is_admin()) {
				// Hide CSS/JS version. Google recommends against using query string for static resources anyway :-)

				if (class_exists('WP_Styles')) {
					add_filter('style_loader_src',  array($this, 'xxx_loader_src'));
					add_action('wp_default_styles', array($this, 'nullify_default_version'), 100);
				}

				if (class_exists('WP_Scripts')) {
					add_action('wp_default_scripts', array($this, 'nullify_default_version'), 100);
					add_filter('script_loader_src',  array($this, 'xxx_loader_src'));
				}
			}

			add_action('init', array($this, 'early_init'), -10000);
			add_action('init', array($this, 'late_init'), 10000);
		}

		public function activate_plugin()
		{
			update_option('ics-security-fixes-active', 1);
		}

		public function deactivate_plugin()
		{
			delete_option('ics-security-fixes-active');
		}

		public function early_init()
		{
			global $wp_version;

			$siteurl = get_option('siteurl');
			$path    = @parse_url($siteurl, PHP_URL_PATH);
			$path    = rtrim($path, '/');

			$self = $_SERVER['REQUEST_URI'];
			if ($path) {
				$self = substr($self, strlen($path));
			}

			if (version_compare($wp_version, '3.0.5', '<')) {
				if (isset($_REQUEST['attachment_id']) && ($id = intval($_REQUEST['attachment_id'])) && $_REQUEST['fetch']) {
					$post = get_post($id);
					if ('attachment' != $post->post_type) {
						wp_die(__( 'Unknown post type.' ));
					}

					if (function_exists('get_post_type_object')) {
						$post_type_object = get_post_type_object('attachment');
						if (!current_user_can($post_type_object->cap->edit_post, $id)) {
							wp_die(__( 'You are not allowed to edit this item.'));
						}
					}
				}
			}
		}

		public function late_init()
		{
			// Hide All in One SEO Pack's version
			if (class_exists('All_in_One_SEO_Pack') && isset($GLOBALS['aiosp']) && $GLOBALS['aiosp'] instanceof All_in_One_SEO_Pack) {
				$GLOBALS['aiosp']->version = '0.0';
			}
		}

		public function nullify_default_version(&$class)
		{
			if (isset($class->default_version)) {
				$class->default_version = '0.0';
			}
		}

		public function authenticate($user, $username, $password)
		{
			// Message from wp_authenticate_username_password(), wp-includes/user.php
			$pattern = sprintf(__('<strong>ERROR</strong>: Invalid username. <a href="%s" title="Password Lost and Found">Lost your password</a>?'), site_url('wp-login.php?action=lostpassword', 'login'));
			// Message from wp_authenticate(), wp-includes/pluggable.php
			$replace  = __('<strong>ERROR</strong>: Invalid username or incorrect password.');

			if ($user instanceof WP_Error) {
				/*
				 * WP_Error does not provide methods to remove a specific code/message.
				 * Its $errors and $error_data properties are marked with @access private;
				 * thus, to be compatible with future WordPress versions we'd better not use them.
				 * The only thing is to re-construct WP_Error sans offending error message(s).
				 */

				$ok = true;

				$messages = array();
				$data     = array();
				foreach ($user->get_error_codes() as $code) {
					$messages[$code] = $user->get_error_messages($code);
					$data[$code]     = $user->get_error_data($code);
				}

				if (isset($messages['incorrect_password'])) {
					$ok = false;
					unset($messages['incorrect_password'], $data['incorrect_password']);
					$messages['authentication_failed'][] = $replace;
				}

				if (isset($messages['invalid_username'])) {
					foreach ($messages['invalid_username'] as $key => $msg) {
						if ($msg == $pattern) {
							if ($ok) {
								$messages['authentication_failed'][] = $replace;
								$ok = false;
							}

							unset($messages['invalid_username'][$key]);
						}
					}

					if (empty($messages['invalid_username'])) {
						unset($messages['invalid_username'], $data['invalid_username']);
					}
				}

				if (!$ok) {
					$user = new WP_Error();
					foreach ($messages as $code => &$arr) {
						if (!empty($arr)) {
							foreach ($arr as $msg) {
								$user->add($code, $msg);
							}

							if (isset($data[$code])) {
								$user->add_data($data[$code], $code);
							}
						}
					}

					unset($arr);
				}
			}

			return $user;
		}

		public function xxx_loader_src($url)
		{
			return preg_replace('!(?:\\?|&)(ver=[^&]+)!', '', $url);
		}

		/**
		 * Fighting SQL truncation
		 */
		public function sanitize_user($username)
		{
			return preg_replace('/\\s+/', ' ', $username);
		}

		public function query($query)
		{
			global $wpdb;

			static $pattern1 = null;
			if (!$pattern1) {
				$pattern1 = "SELECT link_id FROM {$wpdb->prefix}links WHERE link_url LIKE (";
			}

			// WordPress Comments Html Spam Vulnerability
			if ($pattern1 == substr($query, 0, strlen($pattern1))) {
				// There are so many ways to abuse the whitelisting that it is safer to disable it
				return "SELECT 0";
			}

			return $query;
		}

		/**
		 * Funny Pings, Part 1
		 */
		public function add_ping($new)
		{
			global $wpdb;
			return $wpdb->escape($new);
		}

		/**
		 * Funny Pings, Part 2
		 */
		public function get_to_ping($to_ping)
		{
			global $wpdb;

			if (is_array($to_ping) && !empty($to_ping)) {
				foreach ($to_ping as &$value) {
					$value = $wpdb->escape($value);
				}

				unset($value);
				return $to_ping;
			}

			if (is_scalar($to_ping)) {
				// Better safe than sorry
				return $wpdb->escape($to_ping);
			}

			return $to_ping;
		}

		/**
		 * CVE-2008-4769
		 */
		public function request($vars)
		{
			if (is_array($vars)) {
				if (isset($vars['cat'])) {
					$vars['cat'] = abs(intval($vars['cat']));
				}
			}

			return $vars;
		}

		/**
		 * Old slug redirect bug fix
		 */
		public function preprocess_comment($data)
		{
			unset($_POST['wp-old-slug']);
			return $data;
		}

		/**
		 * Tries to prevent file:// redirects (though it is more of a cURL bug) for old cURL (do they still exist)?
		 */
		public function http_api_curl(&$handle)
		{
			if (defined(CURLOPT_REDIR_PROTOCOLS)) {
				curl_setopt($handle, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_ALL & ~(CURLPROTO_FILE | CURLPROTO_SCP));
			}

			if (defined(CURLOPT_PROTOCOLS)) {
				curl_setopt($handle, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_ALL & ~CURLPROTO_FILE);
			}
		}

		/**
		 * Protects against SQL injection attack in wp_insert_attachment() with unfiltered $mime_type
		 */
		public function post_mime_type_pre($type)
		{
			global $wpdb;
			return $wpdb->escape($type);
		}

		public function dashboard_primary_feed()
		{
			return __('http://wordpress.org/development/feed/');
		}

		public function dashboard_secondary_feed()
		{
			return __('http://planet.wordpress.org/feed/');
		}
	}

	ICS_Security_Fixes::instance();

	$GLOBALS['ics_security_fixes_active'] = (1 == get_option('ics-security-fixes-active', 0));

	if ($GLOBALS['ics_security_fixes_active']) {
		if (version_compare($GLOBALS['wp_version'], '2.6.2', '<=')) {
			if (!function_exists('wp_rand')) : // Non-random randoms
				function wp_rand($min = 0, $max = 0)
				{
					global $rnd_value;

					if (strlen($rnd_value) < 8) {
						$seed       = get_option('random_seed');
						$rnd_value  = md5(uniqid(microtime() . mt_rand(), true) . $seed);
						$rnd_value .= sha1($rnd_value);
						$rnd_value .= sha1($rnd_value . $seed);
						$seed       = md5($seed . $rnd_value);
						update_option('random_seed', $seed);
					}

					$value     = substr($rnd_value, 0, 8);
					$rnd_value = substr($rnd_value, 8);
					$value     = abs(hexdec($value));

					if ($max != 0) {
						$value = $min + (($max - $min + 1) * ($value / (4294967295 + 1)));
					}

					return abs(intval($value));
				}
			endif;
		}

		if (version_compare($GLOBALS['wp_version'], '2.7', '>=') && version_compare($GLOBALS['wp_version'], '2.8.5', '<')) {
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
		}

		if ($GLOBALS['wp_version'] == '2.5') { // Fix Wordpress 2.5 Cookie Integrity Protection Vulnerability
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
		}

		if ($GLOBALS['wp_version'] == '2.5.1') {
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
		}

		if (version_compare($GLOBALS['wp_version'], '2.5.1', '>') && version_compare($GLOBALS['wp_version'], '2.6.1', '<=') && function_exists('wp_rand')) {
			if (!function_exists('wp_generate_password')) :
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
		}

		if (version_compare($GLOBALS['wp_version'], '3.0.5', '<') && !function_exists('check_admin_referer')) {
			/* This happens at least from 2.3.0  */
			function check_admin_referer($action = -1, $query_arg = '_wpnonce')
			{
				$adminurl = strtolower(admin_url());
				$referer  = strtolower(wp_get_referer());
				$result   = isset($_REQUEST[$query_arg]) ? wp_verify_nonce($_REQUEST[$query_arg], $action) : false;
				if (!$result && !(-1 == $action && strpos($referer, $adminurl) === 0)) {
					wp_nonce_ays($action);
					die();
				}

				do_action('check_admin_referer', $action, $result);
				return $result;
			}
		}

		if (version_compare($GLOBALS['wp_version'], '2.9.0', '<') && !function_exists('wp_strip_all_tags')) {
			function wp_strip_all_tags($string, $remove_breaks = false)
			{
				$string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
				$string = strip_tags($string);

				if ($remove_breaks) {
					$string = preg_replace('/[\r\n\t ]+/', ' ', $string);
				}

				return trim($string);
			}
		}

		if (version_compare($GLOBALS['wp_version'], '3.0.0', '<') && !function_exists('sanitize_key') && function_exists('wp_strip_all_tags')) {
			function sanitize_key($key)
			{
				$raw_key = $key;
				$key     = wp_strip_all_tags($key);

				$key = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '', $key);
				$key = preg_replace('/&.+?;/', '', $key);
				$key = preg_replace('|[^a-z0-9 _.\-@]|i', '', $key);
				$key = preg_replace('|\s+|', ' ', $key);
				return apply_filters('sanitize_key', $key, $raw_key);
			}
		}
	}

?>