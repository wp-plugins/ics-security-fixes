<?php

if (!function_exists('check_admin_referer')) :
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
endif;

if (defined('ABSPATH')) :
	// wp_kses_data() comes from pre-2.9.0
	add_filter('comment_text', 'wp_kses_data');

	foreach (array('pre_post_status', 'pre_post_comment_status', 'pre_post_ping_status') as $filter) {
		// sanitize_key() comes from pre-3.0.0
		add_filter($filter, 'sanitize_key');
	}

	unset($filter);
endif;

?>