<?php
/**
 * @todo http://core.trac.wordpress.org/changeset/18355
 * There is an issue with get_bookmarks() but it is not easy to fix as WordPress does not offer any hooks to control its arguments
 */
	/**
	 * @see http://www.smoothblog.co.uk/2011/07/01/hack-wordpress-313-32rc1-sql-injection/
	 * @see http://snipper.ru/view/99/wordpress-312-sql-injection-in-wp-adminedit-tagsphp/
	 */
	function icssf_get_terms_args_pre314(array $args)
	{
		global $wpdb;
		$is31up = (version_compare($GLOBALS['wp_version'], '3.1', '>='));

		if (!empty($args['search'])) {
			if (!$is31up) {
				$args['search'] = like_escape($args['search']);
			}

			$args['search'] = $wpdb->escape($args['search']);
		}

		if (isset($args['name__like'])) {
			if (!$is31up) {
				$args['name__like'] = like_escape($args['name__like']);
			}

			$args['name__like'] = $wpdb->escape($args['name__like']);
		}

		if (!empty($args['order'])) {
			$order = strtoupper($args['order']);
			if (!in_array($order, array('ASC', 'DESC'))) {
				$args['order'] = 'ASC';
			}
		}

		$_orderby = empty($args['orderby']) ? strtolower($orderby) : '';
		if ('count' == $_orderby) {
			$orderby = 'tt.count';
		}
		elseif ('name' == $_orderby) {
			$orderby = 't.name';
		}
		elseif ('slug' == $_orderby) {
			$orderby = 't.slug';
		}
		elseif ('term_group' == $_orderby) {
			$orderby = 't.term_group';
		}
		elseif ('none' == $_orderby) {
			$orderby = '';
		}
		elseif (empty($_orderby) || 'id' == $_orderby) {
			$orderby = 't.term_id';
		}
		else {
			$orderby = 't.name';
		}

		$args['orderby'] = $orderby;
		return $args;
	}

if (defined('ABSPATH')) :

	add_filter('get_terms_args', 'icssf_get_terms_args_pre314', 0);

endif;

?>