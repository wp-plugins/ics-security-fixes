<?php

	function icssf_dashboard_primary_feed_pre270()
	{
		return __('http://wordpress.org/development/feed/');
	}

	function icssf_dashboard_secondary_feed_pre270()
	{
		return __('http://planet.wordpress.org/feed/');
	}

if (defined('ABSPATH')) :
	if (version_compare($GLOBALS['wp_version'], '2.5', '>=')) {
		add_filter('dashboard_primary_feed',   'icssf_dashboard_primary_feed_pre270',   1000);
		add_filter('dashboard_secondary_feed', 'icssf_dashboard_secondary_feed_pre270', 1000);
	}
endif;
