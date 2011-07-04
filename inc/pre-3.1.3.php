<?php

if (!function_exists('sanitize_mime_type')) :
	function sanitize_mime_type($mime_type)
	{
		$sani_mime_type = preg_replace('/[^-+*.a-zA-Z0-9\/]/', '', $mime_type);
		return apply_filters('sanitize_mime_type', $sani_mime_type, $mime_type);
	}
endif;

if (!function_exists('send_frame_options_header')) :
	function send_frame_options_header()
	{
		@header('X-Frame-Options: SAMEORIGIN');
	}
endif;

if (defined(ABSPATH)) :
	add_filter('pre_post_guid', 'wp_strip_all_tags');
	add_filter('pre_post_guid', 'esc_url_raw');
	add_filter('pre_post_guid', 'wp_filter_kses');

	is_admin() and add_filter('post_guid', 'wp_strip_all_tags');
	add_filter('post_guid', 'esc_url');
	is_admin() and add_filter('post_guid', 'wp_kses_data');

	add_filter('pre_post_mime_type', 'sanitize_mime_type');
	add_filter('post_mime_type', 'sanitize_mime_type');

	add_action('admin_init', 'send_frame_options_header', 10, 0);

	if (!empty($_SERVER['SCRIPT_FILENAME']) && '/wp-login.php' == substr($_SERVER['SCRIPT_FILENAME'], -13)) {
		send_frame_options_header();
	}
endif;

?>