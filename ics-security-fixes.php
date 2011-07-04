<?php
/*
Plugin Name: ICS Security Fixes
Plugin URI: http://blog.sjinks.pro/wordpress-plugins/ics-security-fixes/
Description: Tries to fix vulnerabilities in outdated WordPress installations
Version: 0.6
Author: ICS
Author URI: http://blog.sjinks.pro/
License: BSD
*/

	defined('ABSPATH') or die();
	$GLOBALS['ics_security_fixes_active'] = 0;

	class ICS_Security_Fixes
	{
		public static $script;

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
			self::$script = $_SERVER['SCRIPT_FILENAME'];
			if (ABSPATH == substr(self::$script, 0, strlen(ABSPATH))) {
				self::$script = substr(self::$script, strlen(ABSPATH)-1);
			}

			register_activation_hook(__FILE__,   array($this, 'activate_plugin'));
			register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));

			if (1 != get_option('ics-security-fixes-active', 0)) {
				return;
			}

			if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
				include dirname(__FILE__) . '/xmlrpc.php';
				ICS_Security_Fixes_XMLRPC::instance();
			}

			add_action('http_api_curl', array($this, 'http_api_curl'));

			remove_action('wp_head', 'wp_generator');
			foreach (array('rss2_head', 'commentsrss2_head', 'rss_head', 'rdf_header', 'atom_head', 'comments_atom_head', 'opml_head', 'app_head') as $action) {
				remove_action($action, 'the_generator');
			}

			// Replace Invalid username and Invalid password messages with a generic one
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

			if (version_compare($wp_version, '3.0.5', '<')) {
				if (isset($_REQUEST['attachment_id']) && ($id = intval($_REQUEST['attachment_id'])) && $_REQUEST['fetch']) {
					$post = get_post($id);
					if ('attachment' != $post->post_type) {
						wp_die(__( 'Unknown post type.' ));
					}

					if (function_exists('get_post_type_object')) {
						$post_type_object = get_post_type_object('attachment');
						if (!current_user_can($post_type_object->cap->edit_post, $id)) {
							wp_die(__('You are not allowed to edit this item.'));
						}
					}
				}
			}

			if (version_compare($wp_version, '3.1', '=')) {
				if (is_admin() && '/wp-admin/media-upload.php' == self::$script) {
					if (isset($_GET['inline'], $_POST['html-upload']) && !empty($_FILES)) {
						check_admin_referer('media-form');
					}
				}
			}

			if (version_compare($wp_version, '2.6', '>=') && version_compare($wp_version, '3.1.2', '<')) {
				if (is_admin() && '/wp-admin/press-this.php' == self::$script) {
					if (isset($_POST['publish']) && !current_user_can('publish_posts')) {
						unset($_POST['publish']);    // All I can do
						unset($_REQUEST['publish']); // Taking care of 2.6.x-2.8.x
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
	}

	ICS_Security_Fixes::instance();

	$GLOBALS['ics_security_fixes_active'] = (1 == get_option('ics-security-fixes-active', 0));

	if ($GLOBALS['ics_security_fixes_active']) {
		if (version_compare($GLOBALS['wp_version'], '2.3', '<')) {
			require dirname(__FILE__) . '/inc/pre-2.3.0.php';
		}

		if (version_compare($GLOBALS['wp_version'], '2.5', '<')) {
			require dirname(__FILE__) . '/inc/pre-2.5.0.php';
		}

		if (version_compare($GLOBALS['wp_version'], '2.5.2', '<')) {
			require dirname(__FILE__) . '/inc/pre-2.5.2.php';
		}

		if (version_compare($GLOBALS['wp_version'], '2.6.2', '<')) {
			require dirname(__FILE__) . '/inc/pre-2.6.2.php';
		}

		if (version_compare($GLOBALS['wp_version'], '2.6.3', '<')) {
			require dirname(__FILE__) . '/inc/pre-2.6.3.php';
		}

		if (version_compare($GLOBALS['wp_version'], '2.7', '<')) {
			require dirname(__FILE__) . '/inc/pre-2.7.0.php';
		}

		if (version_compare($GLOBALS['wp_version'], '2.8', '<')) {
			require dirname(__FILE__) . '/inc/pre-2.8.0.php';
		}

		if (version_compare($GLOBALS['wp_version'], '2.8.5', '<')) {
			require dirname(__FILE__) . '/inc/pre-2.8.5.php';
		}

		if (version_compare($GLOBALS['wp_version'], '2.9', '<')) {
			require dirname(__FILE__) . '/inc/pre-2.9.0.php';
		}

		if (version_compare($GLOBALS['wp_version'], '3.0', '<')) {
			require dirname(__FILE__) . '/inc/pre-3.0.0.php';
		}

		if (version_compare($GLOBALS['wp_version'], '3.0.2', '<')) {
			require dirname(__FILE__) . '/inc/pre-3.0.2.php';
		}

		if (version_compare($GLOBALS['wp_version'], '3.0.4', '<')) {
			require dirname(__FILE__) . '/inc/pre-3.0.4.php';
		}

		if (version_compare($GLOBALS['wp_version'], '3.0.5', '<')) {
			require dirname(__FILE__) . '/inc/pre-3.0.5.php';
		}

		if (version_compare($GLOBALS['wp_version'], '3.1', '<')) {
			require dirname(__FILE__) . '/inc/pre-3.1.0.php';
		}

		if (version_compare($GLOBALS['wp_version'], '3.1.1', '<')) {
			require dirname(__FILE__) . '/inc/pre-3.1.1.php';
		}

		if (version_compare($GLOBALS['wp_version'], '3.1.3', '<')) {
			require dirname(__FILE__) . '/inc/pre-3.1.3.php';
		}

		if (version_compare($GLOBALS['wp_version'], '3.1.4', '<')) {
			require dirname(__FILE__) . '/inc/pre-3.1.4.php';
		}
	}

?>