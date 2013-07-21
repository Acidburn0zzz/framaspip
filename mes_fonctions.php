<?php

/**
 * Proxy API wikipedia :
 * utilise l'api wikipedia
 *
 * @param string $url
 * @param string $what
 * @return array|string
 */
function wikipedia_content($url, $what="html"){
	include_spip("inc/frama");
		$content = frama_wikipedia_content($url);

	if (!$content OR !isset($content[$what]))
		return "";

	return $content[$what];
}
