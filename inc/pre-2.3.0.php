<?php

	/**
	 * Protects against SQL injection attack in wp_insert_attachment() with unfiltered $mime_type
	 */
	function icssf_post_mime_type_pre_pre230($type)
	{
		global $wpdb;
		return $wpdb->escape($type);
	}

if (defined('ABSPATH')) :
	add_filter('post_mime_type_pre', 'icssf_post_mime_type_pre_pre230');
endif;