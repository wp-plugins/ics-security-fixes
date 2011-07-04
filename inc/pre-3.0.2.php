<?php

	function icssf_query_pre302($query)
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
	 * Funny Pings, Part 2
	 */
	function icssf_get_to_ping_pre302($to_ping)
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


if (defined('ABSPATH')) :
	add_filter('query',       'icssf_query_pre302');
	add_filter('get_to_ping', 'icssf_get_to_ping_pre302');
endif;
