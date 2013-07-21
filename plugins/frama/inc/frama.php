<?php
/**
 * Plugin Frama
 * Licence GPL
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;


if (!defined('_DUREE_CACHE_HTML_PAGE'))
	define('_DUREE_CACHE_HTML_PAGE',24*3600);

/**
 * Recuperer une URL distante avec un cache file d'une 1H
 * et utilisation du not-modified-since au dela
 *
 * @param $url
 * @return bool|int|string
 */
function frama_recuperer_page_cache($url){
	static $now = null;
	if (!$now) $now = time();

	$cache = md5($url);
	$dir = sous_repertoire(_DIR_CACHE,substr($cache,0,1));
	$cache = $dir."htmlcache-$cache.html";

	$date = 0;
	if (_VAR_MODE
		OR !file_exists($cache)
	  OR !$date=filemtime($cache)
	  OR $date<$now-_DUREE_CACHE_HTML_PAGE){

		include_spip('inc/distant');
		copie_locale($url,_VAR_MODE?'force':'modif',$cache);
	}

	lire_fichier($cache,$html);
	return $html;
}


/**
 * Proxy API wikipedia :
 * utilise l'api wikipedia
 *
 * @param string $url
 * @return array|string
 */
function frama_wikipedia_content($url){
	static $content = array();

	if (!isset($content[$url])){
		if (!$url
			OR !preg_match(',\w+://([\w]+).wikipedia.org/wiki/([^/]+).*,i',$url,$m))
			return $content[$url] = false;

		$sous = $m[1];
		$page = $m[2];
		unset($m);

		// ajouter &section=0 pour se limiter a la premiere section
		$api = "http://$sous.wikipedia.org/w/api.php?action=parse&page=$page&format=xml&section=0";
		$string = frama_recuperer_page_cache($api);

		$xml = simplexml_load_string($string);

		$title = (string)$xml->parse->attributes()->displaytitle;
		$html = (string)$xml->parse->text;
		$html = liens_absolus($html,$url);

		if (preg_match(",REDIRECTION\s*<a\b.*</a>,Uims",$html,$m)){
			$link = extraire_balise($html,"a");
			$link = extraire_attribut($link,"href");
			return frama_wikipedia_content($link);
		}

		// extraire le logo et documents (images de plus de 50px)
		$logo = "";
		if ($images = extraire_balises($html,"img")){
			$srcs = array();
			foreach($images as $i){
				$t = taille_image($i);
				// que les images de plus de 50px
				if (reset($t)>50 OR end($t)>50){
					$src = extraire_attribut($i,"src");
					if (strncmp($src,"//",2)==0)
						$src = "http:".$src;
					if (strpos($src,"thumb/")!==false){
						$srcfull = str_replace("thumb/","",$src);
						$srcfull = preg_replace(",/[^/]*$,Uims","",$srcfull);
						if (count($srcs) OR preg_match(",[.](png|gif|jpe?g)$,",$srcfull))
							$src = $srcfull;
					}
					$srcs[] = $src;
				}
			}
			if (count($srcs))
				$logo = array_shift($srcs);
		}

		// mise en forme tableaux et extraction cartouche
		$tables = extraire_balises($html,"table");
		$cartouche = "";
		foreach($tables as $table){
			$p = strpos($html,$table);
			$html = substr($html,$p+strlen($table));
			$t = inserer_attribut($table,"class","spip");
			$cartouche .= $t;
		}
		// supprimer les <tr><td><hr>
		$cartouche = preg_replace(",<tr[^>]*>\s*<td[^>]*>\s*<hr[^>]*>\s*</td>\s*</tr>,Uims","",$cartouche);


		$content[$url] = array(
			// type (required)
	    // The resource type. Valid values, along with value-specific parameters, are described below.
			'type' => 'rich',

			// version (required)
	    // The oEmbed version number. This must be 1.0.
			'version' => '1.0',

			// title (optional)
	    // A text title, describing the resource.
			'title' => $title,

			// html (required)
	    // The HTML required to display the resource. The HTML should have no padding or margins. Consumers may wish to load the HTML in an off-domain iframe to avoid XSS vulnerabilities. The markup should be valid XHTML 1.0 Basic.
			'html' => $html,

			'infobox' => $cartouche,

			'images' => $srcs,

			// author_name (optional)
	    // The name of the author/owner of the resource.
			// NIY
			// 'author_name' => '',

			// author_url (optional)
	    // A URL for the author/owner of the resource.
			// NIY
			// 'author_url' => '',


			// thumbnail_url (optional)
	    // A URL to a thumbnail image representing the resource. The thumbnail must respect any maxwidth and maxheight parameters. If this paramater is present, thumbnail_width and thumbnail_height must also be present.
			// NIY
			'thumbnail_url' => $logo,

			// thumbnail_width (optional)
	    // The width of the optional thumbnail. If this paramater is present, thumbnail_url and thumbnail_height must also be present.
			// NIY
			// 'thumbnail_width' => '',

			// thumbnail_height (optional)
	    // The height of the optional thumbnail. If this paramater is present, thumbnail_url and thumbnail_width must also be present.
			// NIY
			// 'thumbnail_height' => '',

			'url' => $url,

		);
	}

	return $content[$url];
}