<?php
/**
 * Plugin Frama
 * Licence GPL
 *
 */

if (!defined("_ECRIRE_INC_VERSION")) return;


function autoriser_article_preremplir_dist($faire, $type='', $id=0, $qui = NULL, $opt = NULL){
	if (intval($id)) return false;
	return
		$qui['statut'] == '0minirezo'
		AND !$qui['restreint'];
}


function frama_formulaire_fond($flux){
	if ($flux['args']['form']=='editer_article'
		AND include_spip("inc/autoriser")
	  AND autoriser("preremplir","article",$flux['args']['args']['id'])){
		if (preg_match(",<\w+\b[^>]+class=['\"]editer editer_titre,Uims",$flux['data'],$m)){
			$p = strpos($flux['data'],$m[0]);
			$pre = recuperer_fond("formulaires/inc-editer_article_preremplir",$flux['args']['contexte']);
			$flux['data'] = substr_replace($flux['data'],$pre,$p,0);
		}
	}
	return $flux;
}

function frama_formulaire_charger($flux){
	if ($flux['args']['form']=='editer_article'
	  AND include_spip("inc/autoriser")
	  AND autoriser("preremplir","article",$flux['args']['args'][0])
	){
		$flux['data']['url_auto'] = '';
		$flux['data']['logo'] = '';
		$flux['data']['format_logo'] = '';
	}
	return $flux;
}

function frama_formulaire_verifier($flux){
	if ($flux['args']['form']=='editer_article'
	  AND include_spip("inc/autoriser")
	  AND autoriser("preremplir","article",$flux['args']['args'][0])
	){

			// Envoi depuis le formulaire d'analyse automatique d'un site
		if (_request('ajoute_url_auto') AND strlen(vider_url($u = _request('url_auto')))) {
			if ($auto = frama_analyser_wikipedia($u)) {
				foreach($auto as $k=>$v){
					set_request($k,$v);
				}
				$flux['data']['verif_url_auto'] = _T('frama:texte_referencement_automatique_verifier', array('url' => $u));
			}
			else{
				$flux['data']['url_auto'] = _T('frama:avis_site_introuvable');
			}
		}
	}

	return $flux;
}


function frama_formulaire_traiter($flux){
	if ($flux['args']['form']=='editer_article'
	  AND include_spip("inc/autoriser")
	  AND autoriser("preremplir","article",$flux['args']['args'][0])
		AND $id_article = $flux['data']['id_article']
	){
		// recuperer le logo si dispo
		if ($logo = _request('logo') AND $format_logo = _request('format_logo')){

		}
	}

	return $flux;
}

function frama_analyser_wikipedia($url){
	include_spip("inc/frama");
	include_spip("inc/sale");
	include_spip("inc/distant");
	$content = frama_wikipedia_content($url);
	if (!$content)
		return false;

	$auto = array();
	$auto['titre'] = $content['title'];

	// extraire le contenu texte
	$texte = $content['html'];
	$texte = sale($texte);
	$texte = strip_tags($texte);
	// nettoyer les notes
	$texte = preg_replace(",\[\[\d+]->#cite_note-[^\]]+\],Uims","",$texte);
	$texte = str_replace(",.",".",$texte);

	// delinker
	include_spip("inc/lien");
	$texte = preg_replace(_RACCOURCI_LIEN,"\\1",$texte);

	$texte = str_replace("\r\n","\n",$texte);
	$texte = str_replace("\r","\n",$texte);
	$texte = explode("\n\n",$texte);

	$auto['descriptif'] = array_shift($texte);
	$auto['texte'] = trim(implode("\n\n",$texte));
	$auto['url_wikipedia'] = $url;

	if ($content['logo']
	  AND $image = recuperer_infos_distantes($content['logo'])) {
		if (in_array($image['extension'], array('gif', 'jpg', 'png'))) {
			$auto['format_logo'] = $image['extension'];
			$auto['logo'] = $content['logo'];
		}
		else if ($image['fichier']) {
			spip_unlink($image['fichier']);
		}
	}

	// extraire le site web de l'infobox
	if ($content['infobox']){
		// extraire toutes les lignes
		preg_match_all(",<tr[^>]*>(.*)</tr>,Uims",$content['infobox'],$matches,PREG_SET_ORDER);
		if (count($matches)){
			foreach($matches as $m){
				if (preg_match(",<th[^>]*>(.*)</th>,Uims",$m[1],$r)
				  AND $t = trim(strip_tags($r[1]))
					AND $t == "Site web"
				){
					$l = explode("<td",$m[1]);
					$l = extraire_balise(end($l),"a");
					$l = extraire_attribut($l,"href");
					$auto['url_site'] = $l;
					$auto['nom_site'] = "Site officiel";
				}
			}
		}
	}

	if (count($content['images'])){
		$ajouter_documents = charger_fonction("ajouter_documents","action");
		include_spip("action/editer_liens");
		$files = array();
		foreach($content['images'] as $src){
			if ($id = sql_getfetsel("id_document","spip_documents","fichier=".sql_quote($src)." AND distant=".sql_quote('oui'))){
				objet_associer(array('document'=>$id),array('article'=>-$GLOBALS['visiteur_session']['id_auteur']));
			}
			else {
				$files[] = array(
					'tmp_name' => $src,
					'distant' => 'oui'
				);
			}
		}
		if (count($files)){
			$ajouter_documents('new', $files, 'article', -$GLOBALS['visiteur_session']['id_auteur'], 'auto');
		}
	}

	return $auto;
}