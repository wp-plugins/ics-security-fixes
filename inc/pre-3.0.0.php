<?php

// wp_strip_all_tags() comes from pre-2.9.0.php
if (!function_exists('sanitize_key')) :
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
endif;

	/**
	 * Old slug redirect bug fix
	 */
	function icsf_preprocess_comment_pre300($data)
	{
		unset($_POST['wp-old-slug']);
		return $data;
	}

if (defined(ABSPATH)) :
	add_filter('preprocess_comment', 'icsf_preprocess_comment_pre300');
endif;
