<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('formulaires/editer_article');

// http://doc.spip.org/@inc_editer_article_dist
function formulaires_proposer_article_charger_dist($id_article='new', $id_rubrique=0, $retour='', $lier_trad=0, $config_fonc='articles_edit_config', $row=array(), $hidden=''){
	$valeurs = formulaires_editer_objet_charger('article',$id_article,$id_rubrique,$lier_trad,$retour,$config_fonc,$row,$hidden);
	$valeurs['_hidden'] = str_replace('editer_article', 'proposer_article', $valeurs['_hidden']);
	// preciser que le formulaire doit etre securise auteur/action
	$valeurs['_action'] = array('proposer_article',$id_article);
	return $valeurs;
}

/**
 * Identifier le formulaire en faisant abstraction des parametres qui
 * ne representent pas l'objet edite
 */
function formulaires_proposer_article_identifier_dist($id_article='new', $id_rubrique=0, $retour='', $lier_trad=0, $config_fonc='articles_edit_config', $row=array(), $hidden=''){
	return serialize(array(intval($id_article),$lier_trad));
}

function formulaires_proposer_article_verifier_dist($id_article='new', $id_rubrique=0, $retour='', $lier_trad=0, $config_fonc='articles_edit_config', $row=array(), $hidden=''){

	$oblis = array(
		0 => 'titre',
		1 => 'url_site',
		2 => 'texte'
	);

	foreach($oblis as $obli) {
		if (!_request($obli)) {
			if (!isset($erreurs[$obli])) { $erreurs[$obli] = ''; }
			$erreurs[$obli] .= _T("info_obligatoire");
		}
	}

	if (!function_exists('autoriser'))
		include_spip('inc/autoriser');	 // si on utilise le formulaire dans le public
	if (!isset($erreurs['id_parent'])
	  AND !autoriser('creerarticledans','rubrique',_request('id_parent'))){
		$erreurs['id_parent'] = _T('info_creerdansrubrique_non_autorise');
	}
	return $erreurs;
}

// http://doc.spip.org/@inc_proposer_article_dist
function formulaires_proposer_article_traiter_dist($id_article='new', $id_rubrique=0, $retour='', $lier_trad=0, $config_fonc='articles_edit_config', $row=array(), $hidden=''){
	$res = formulaires_editer_article_traiter_dist($id_article, $id_rubrique, $retour, $lier_trad, $config_fonc, $row, $hidden);
	$res['message_ok'] = _T('frama:proposer_message_merci');
	$res['redirect'] = parametre_url($retour);
	return $res;
}

?>
