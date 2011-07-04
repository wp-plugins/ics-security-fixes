<?php

/**
 * _deep_replace is private and appeared in 2.8.1
 */
function icssf_deep_replace(array $search, $subject)
{
	$subject = (string)$subject;
	do {
		$found = false;
		foreach ($search as $val) {
			while (strpos($subject, $val) !== false) {
				$found   = true;
				$subject = str_replace($val, '', $subject);
			}
		}
	} while ($found);

	return $subject;
}

if (!function_exists('esc_url')) :
	function esc_url($url, $protocols = null, $_context = 'display')
	{
		if (empty($url)) {
			return $url;
		}

		$original_url = $url;

		$url   = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);
		$strip = array('%0d', '%0a', '%0D', '%0A');
		$url   = icssf_deep_replace($strip, $url);
		$url   = str_replace(';//', '://', $url);

		if (strpos($url, ':') === false && substr($url, 0, 1) != '/' && substr($url, 0, 1) != '#' && !preg_match('/^[a-z0-9-]+?\.php/i', $url)) {
			$url = 'http://' . $url;
		}

		if ('display' == $_context) {
			$url = wp_kses_normalize_entities( $url );
			$url = str_replace(array('&amp;', "'"), array('&#038;', '&#039;'), $url );
		}

		if (!is_array($protocols)) {
			$protocols = array ('http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'svn');
		}

		if (wp_kses_bad_protocol($url, $protocols) != $url) {
			return '';
		}

		return apply_filters('clean_url', $url, $original_url, $_context);
	}
endif;

if (!function_exists('esc_url_raw')) :
	function esc_url_raw($url, $protocols = null) { return esc_url($url, $protocols, 'db'); }
endif;

if (defined(ABSPATH)) :
	foreach (array('pre_comment_author_url', 'pre_user_url', 'pre_link_url', 'pre_link_image', 'pre_link_rss') as $filter) {
		add_filter($filter, 'esc_url_raw', 11);
	}

	foreach (array('user_url', 'link_url', 'link_image', 'link_rss', 'comment_url') as $filter) {
		add_filter($filter, 'esc_url', 11);
	}

endif;
?>