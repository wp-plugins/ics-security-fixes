<?php

	if (defined('WP_UNINSTALL_PLUGIN') && WP_UNINSTALL_PLUGIN) {
		delete_option('ics-security-fixes-active');
	}

?>