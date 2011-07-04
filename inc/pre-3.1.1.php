<?php

	/**
	 * Reduce PCRE recusrion limit for make_clickable() to 10,000 to aviod segfaults.
	 * @see http://core.trac.wordpress.org/ticket/16892
	 */
	function icssf_make_clickable_reduce_recursion_limit_pre311($what)
	{
		$_GLOBALS['icssf_pcre_recursion_limit'] = @ini_set('pcre.recursion_limit', 10000);
		return $what;
	}

	/**
	 * Restore PCRE recusrion limit after make_clickable()
	 * @see http://core.trac.wordpress.org/ticket/16892
	 */
	function icssf_make_clickable_restore_recursion_limit_pre311($what)
	{
		if ($_GLOBALS['icssf_pcre_recursion_limit'] > 0) {
			@ini_set('pcre.recursion_limit', $_GLOBALS['icssf_pcre_recursion_limit']);
		}

		return $what;
	}


if (defined('ABSPATH')) :
	$_GLOBALS['icssf_pcre_recursion_limit'] = -1;
	add_filter('comment_text', 'icssf_make_clickable_reduce_recursion_limit_pre311', 0);
	add_filter('comment_text', 'icssf_make_clickable_restore_recursion_limit_pre311', 99);
endif;

?>