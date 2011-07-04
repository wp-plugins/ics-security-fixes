<?php

	function icssf_clean_url_pre304($url, $original_url, $context)
	{
		if ('display' == $context) {
			$url = wp_kses_normalize_entities($url);
			$url = str_replace('&amp;', '&#038;', $url);
		}

		return $url;
	}


if (defined('ABSPATH')) :
	version_compare($GLOBALS['wp_version'], '2.3.1', '>=') and add_filter('clean_url', 'icssf_clean_url_pre304', 10, 3);
endif;
