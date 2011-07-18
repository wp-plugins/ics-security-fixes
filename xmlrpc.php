<?php

	defined('ABSPATH') or die();

	class ICS_Security_Fixes_XMLRPC
	{
		public static function instance()
		{
			static $self = null;
			if (!$self) {
				$self = new ICS_Security_Fixes_XMLRPC();
			}

			return $self;
		}

		private function __construct()
		{
			global $wp_version;

			if (version_compare($wp_version, '3.0.3', '<')) {
				add_action('trash_comment',     array($this, 'trash_delete_comment'), 0);
				add_action('delete_comment',    array($this, 'trash_delete_comment'), 0);
				add_action('xmlrcp_call',       array($this, 'xmlrpc_call'), 0);
				add_action('delete_post',       array($this, 'delete_post'), 0);
				add_action('delete_attachment', array($this, 'delete_post'), 0);
				add_action('trash_post',        array($this, 'delete_post'), 0);

				add_filter('wp_insert_post_data', array($this, 'wp_insert_post_data'), 0, 2);

				wp_update_post();
			}
		}

		private static function xmlrpc_error($code, $message)
		{
			$message = htmlspecialchars($message, ENT_QUOTES, get_bloginfo('charset'));
			$xml = <<< delimiter
<?xml version="1.0"?>
<methodResponse>
	<fault>
		<value>
			<struct>
				<member>
					<name>faultCode</name>
					<value><int>{$code}</int></value>
				</member>
				<member>
					<name>faultString</name>
					<value><string>{$message}</string></value>
				</member>
			</struct>
		</value>
	</fault>
</methodResponse>
delimiter;

			$len = strlen($xml);
			header('Connection: close');
			header('Content-Length: ' . $len);
			header('Content-Type: text/xml');
			header('Date: ' . date('r'));
			die($xml);
		}

		public function trash_delete_comment($comment_ID)
		{
			$comment = get_comment($comment_ID);
			if (!$comment) {
				self::xmlrpc_error(404, __('Invalid comment ID.'));
			}

			if (!current_user_can('edit_post', $comment->comment_post_ID)) {
				self::xmlrpc_error(403, __('You are not allowed to moderate comments on this site.'));
			}
		}

		public function xmlrpc_call($method)
		{
			switch ($method) {
				case 'wp.getPageStatusList':
					if (!current_user_can('edit_posts')) {
						self::xmlrpc_error(403, __('You are not allowed access to details about this site.'));
					}

					break;
			}
		}

		public function delete_post($post_ID)
		{
			if (!current_user_can('delete_post', $post_ID)) {
				self::xmlrpc_error(401, __('Sorry, you do not have the right to delete this post.'));
			}
		}

		public function wp_insert_post_data($data, $postarr)
		{
			if (isset($postarr['ID'])) {
				$post_ID = (int)$postarr['ID'];
				if (!current_user_can('edit_post', $post_ID)) {
					self::xmlrpc_error(401, __('Sorry, you cannot edit this post.'));
				}
			}

			return $data;
		}
	}

?>