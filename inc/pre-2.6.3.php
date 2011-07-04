<?php
if (!function_exists('wp_rand')) : // Non-random randoms
	function wp_rand($min = 0, $max = 0)
	{
		global $rnd_value;

		if (strlen($rnd_value) < 8) {
			$seed       = get_option('random_seed');
			$rnd_value  = md5(uniqid(microtime() . mt_rand(), true) . $seed);
			$rnd_value .= sha1($rnd_value);
			$rnd_value .= sha1($rnd_value . $seed);
			$seed       = md5($seed . $rnd_value);
			update_option('random_seed', $seed);
		}

		$value     = substr($rnd_value, 0, 8);
		$rnd_value = substr($rnd_value, 8);
		$value     = abs(hexdec($value));

		if ($max != 0) {
			$value = $min + (($max - $min + 1) * ($value / (4294967295 + 1)));
		}

		return abs(intval($value));
	}
endif;

