<?php

if (!function_exists('wp_kses_data')) :
	function wp_kses_data($data)
	{
		return wp_kses($data, $GLOBALS['allowedtags']);
	}
endif;

if (!function_exists('wp_strip_all_tags')) :
	function wp_strip_all_tags($string, $remove_breaks = false)
	{
		$string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
		$string = strip_tags($string);

		if ($remove_breaks) {
			$string = preg_replace('/[\r\n\t ]+/', ' ', $string);
		}

		return trim($string);
	}
endif;
