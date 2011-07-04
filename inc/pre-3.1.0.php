<?php

	function icssf_check_admin_referer_media_form_311()
	{
		check_admin_referer('media-form');
	}

if (defined('ABSPATH')) :
	if (version_compare($GLOBALS['wp_version'], '3.1', '=')) {
		add_action('media_upload_image', 'icssf_check_admin_referer_media_form_311', 0);
		add_action('media_upload_audio', 'icssf_check_admin_referer_media_form_311', 0);
		add_action('media_upload_video', 'icssf_check_admin_referer_media_form_311', 0);
		add_action('media_upload_file',  'icssf_check_admin_referer_media_form_311', 0);
	}
endif;