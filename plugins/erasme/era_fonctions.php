<?php

include_spip('inc/cextras_autoriser');

$fieldsProjet = array(
    'rubriques_associees',
    'description_title',
    'description',
    'description_title_second',
    'description_second',
    'chiffres_cles',
    'description_lateral_title',
    'description_lateral',
    'developpement',
    'fieldset_1',
    'fieldset_3',
    'fieldset_5',
    'descr_tech_technique',
    'descr_tech_devices',
    'descr_tech_framework',
    'descr_tech_depot',
    'descr_tech_licence',
    'information_site_web',
    'information_mailing',
    'information_communaute_utilisateurs',
    'information_entreprises',
    'description_title_third',
    'description_third',
    'media',
    'id_linked_rub',
    'auteur_compte_twitter',
    'auteur_compte_linkedin',
);

// Doc de cette fonction ici : https://contrib.spip.net/Champs-Extras-3-API-et-creations
restreindre_extras('article', $fieldsProjet, array(308), 'rubrique', true); // TODO : admin

/**
 * Cette fonction tronque les chaînes de caractères en leur concaténant, si demandé, un texte à la fin.
 *
 * @param  string $texteATronquer        Le texte à tronquer si
 *                                       nécessaire.
 * @param  int    $nbDeCaracteresAGarder Le nombre MAXIMAL de caractères à garder dans la chaîne
 *                                       reçue.
 * @param  string $texteARajouterALaFin  Si besoin, on peut rajouter du texte après la troncature (comme
 *                                       « ... »)
 * @return string
 */
function troncature_propre($texteATronquer='', $nbDeCaracteresAGarder=25, $texteARajouterALaFin='...')
{
    if (mb_strlen($texteATronquer) < $nbDeCaracteresAGarder) {
        return $texteATronquer;
    }
    return mb_substr($texteATronquer, 0, $nbDeCaracteresAGarder) . '...';
}


/////////////////////////////////////////////// VUE ////////////////////////////////////////////////////////////////////

/*
 * HEADER
 */

/**
 * Dans l'en-tête de la page d'accueil, on a plusieurs blocs. Le bloc « EVEN » doit correspondre
 * à la rubrique « Information ».
 *
 * @param  string $titre
 * @return string
 */
function filtre_titre_header($titre='')
{
    if($titre==='Information') {
        return 'Evenement';
    }
    return $titre;
}

/**
 * Nettoie la chaîne de caractères donnée pour qu'elle puisse être utilisée comme nom de classe.
 *
 * @param  string $nomClasseCrado
 * @return string|string[]
 */
function nettoyerNomClasse($nomClasseCrado='')
{
    /*
    * Pour l'instant, on supprime :
    * - les espaces
    */
    return str_replace(' ', '_', trim($nomClasseCrado));
}


function filtre_nettoyer_nom_classe($nomClasse='')
{
    return str_replace(' ', '_', $nomClasse);
}



/*
 * VUE PROTOTYPE
 */


/**
 * Renvoie le code HTML de la timeline dont les informations sont reçues en entrée.
 *
 * @param  string $texte_a_decouper
 * @return string
 */
function getTimeline($texte_a_decouper='')
{
    // Cast de sécurité en string.
    $texte_a_decouper = strval($texte_a_decouper);
    // Si pas d'information reçue, on ne renvoie rien.
    if (trim($texte_a_decouper) == '') {
        return '';
    }
    $dates = explode("\n", $texte_a_decouper);
    // Par sécurité, on vérifie qu'au moins une année est présente.
    if (count($dates)==0) {
        return '';
    }

    $texteDecoupe = '<div class="timeline">
					<ul>';

    foreach ($dates as $date){
        $infos = explode(':', $date);
        // Une ligne de texte ne doit contenir que sa date ainsi que la phase qui lui correspond. On ignorera purement
        // et simplement ce qui déroge à cette règle.
        if (count($infos)==2) {
            $classeActive = ''; // On mettra d'un autre style, les évènements de la ligne temporelle ayant un « > ».
            if(mb_substr($infos[0], 0, 1)=='>' || mb_substr($infos[0], 0, 1)=="&gt;") {
                $classeActive = ' active';
            }
            $tailleTexte = 40;
            $texteDecoupe .= '
						<li class="event' . $classeActive . '">
							<p class="title"><abbr title="' . $infos[1] . '">' . mb_substr($infos[1], 0, $tailleTexte) . '</abbr></p>
							<div class="arrow-down"></div>
							<div class="circle"></div>
							<p class="date">' . str_replace(array('>', "&gt;"), '', $infos[0]) . '</p>
						</li>';
        }
    }
    $texteDecoupe .= '
					</ul>
					<div class="line"></div>
				</div>';
    return $texteDecoupe;
}

/**
 * Extraie l'identifiant d'une image qui aurait été insérée dans le texte.
 *
 * Parfois certains contenus textuels contiennent des balises de type <img4806|left>. Il s'agit d'un code historique
 * qui permet l'insertion d'une image. Afin de maintenir le fonctionnement des précédents textes, on extraira les
 * identifiants de ces balises pour en renvoyer le premier qu'on trouvera.
 *
 * @param  string $texteDescriptif
 * @return string
 */
function recuperer_id_image($texteDescriptif='')
{
    $pos = strpos($texteDescriptif, '<img');
    if ($pos === false) {
        return false;
    } else {
        $idImage = '';
        $compteur = 4;
        while (true){
            if($texteDescriptif[$pos+$compteur]=='|') {
                return $idImage;
            }
            $idImage .= $texteDescriptif[$pos+$compteur];
            $compteur +=1;
        }
    }
}


/**
 * On créé à la volée le code du bloc des chiffres-clef.
 *
 * @param  string $texteBrut Le texte brut du champ chiffre-clef.
 * @return string Le code HTML correspondant au bloc.
 */
function getInformationsChiffresClef($texteBrut='')
{
    // Cas basique vide ou texte par défaut oublié.
    if($texteBrut=='' || mb_substr($texteBrut, 0, 3)=='Ex.' ) {
        return '';
    }

    $codeChiffresClef = '<h2>Chiffres clé</h2>';
    $codeChiffresClef.= '<ul class="chiffre-container">';
    $lignes = explode("\n", $texteBrut);
    foreach ($lignes as $ligne){
        $codeChiffresClef.= '<li class="chiffre">';
        $contenus = explode(' ', $ligne);
        // operateur ternaire au cas où la lgne commencerait par un "> ". Dans ce cas on décalera ce qu'on veut par la droite.
        $chiffre = $contenus[0]=='>' ? $contenus[1] : $contenus[0];
        $texte = $contenus[0]=='>' ? implode(' ', array_slice($contenus, 2, count($contenus))) : implode(' ', array_slice($contenus, 1, count($contenus)));
        $codeChiffresClef .= '<div class="number"><p>' . $chiffre . '</p></div>';
        $codeChiffresClef .= '<p>' . $texte . '</p>';
        $codeChiffresClef.= '</li>';
    }
    $codeChiffresClef.= '</ul>';
    return $codeChiffresClef;
}

/**
 * Dns la vue prototype, il y a une section nommée « Écosystème ». Cette section contient des
 * « marguerites » dessinées et représentant les utilisateurs, les partenaires ainsi que les entreprises qui
 * y participent.
 *
 * @param  string $logo_url_absolue
 * @param  string $titre
 * @param  string $logo
 * @return string
 */
function codePetaleMargueriteEcosysteme($logo_url_absolue='', $titre='', $logo='')
{
    if ($logo==='') {
        if(mb_strlen($titre) < 30 ) { // On ne veut pas de texte trop long, sinon cela déborde du pétale...
            return '<li class="petale_marguerite_sans_image">' . $titre . '</li>';
        }
        return '<li class="petale_marguerite_sans_image">' . mb_substr($titre, 0, 30) . '...</li>';
    } else{
        return '<li class="img petale_marguerite_avec_image" style="background-image:url(\'' . $logo_url_absolue . '\')"></li>';
    }
}

/**
 * Si le sous-titre est vide (pas bien !), on affichera le titre à la place.
 *
 * @param  string $titre
 * @param  string $sousTitre
 * @return string
 */
function choisirTitre($titre='', $sousTitre='')
{
    if ($sousTitre==='') {
        return $titre;
    }
    return $sousTitre;
}


/**
 * Génère tout bêtement le bloc de descriptif technique en bas des pages prototypes. Trop de cas possibles à gérer en
 * SPIP, je l'ai donc simplifié et déporté ici.
 *
 * @param  string $descr_tech_technique
 * @param  string $descr_tech_devices
 * @param  string $descr_tech_framework
 * @param  string $descr_tech_depot
 * @param  string $descr_tech_licence
 * @return string
 */
function injecterBlocDescriptifTechnique($descr_tech_technique='', $descr_tech_devices='', $descr_tech_framework='', $descr_tech_depot='', $descr_tech_licence='')
{
    $codeBloc = '';
    if (trim($descr_tech_technique)!=='') {
        $codeBloc .= '
			<li>
				<h4>Types techniques :</h4>
				<p>' . $descr_tech_technique . '</p>
			</li>';
    }
    if (trim($descr_tech_devices)!=='') {
        $codeBloc .= '
			<li>
				<h4>Devices / Compatibilité :</h4>
				<p>' . $descr_tech_devices . '</p>
			</li>';
    }
    if (trim($descr_tech_framework)!=='') {
        $codeBloc .= '
			<li>
				<h4>Framework :</h4>
				<p>' . $descr_tech_framework . '</p>
			</li>';
    }
    if (trim($descr_tech_depot)!=='') {
        $codeBloc .= '
			<li>
				<h4>Dépôt :</h4>
				<p><a href="' . $descr_tech_depot . '">' . $descr_tech_depot . '</a></p>
			</li>';
    }
    if (trim($descr_tech_licence)!=='') {
        $codeBloc .= '
			<li>
				<h4>Licence :</h4>
				<p>' . $descr_tech_licence . '</p>
			</li>';
    }
    // Si $codeBloc n'est pas vide, cela vaut le coup de générer le code HTML l'englobant.
    if($codeBloc!=='') {
        $codeBloc = '<div class="col">
			<h2 id="titreDescriptifTechnique">Descriptif technique</h2>
			<ul id="listeDescriptifTechnique">
		' . $codeBloc . '
			</ul>
		</div>';
    }
    return $codeBloc;
}

function injecterBlocInformations($information_site_web='', $information_mailing='', $information_communaute_utilisateur='', $information_entreprises='')
{
    $codeBloc = '';
    if (trim($information_site_web)!=='') {
        $codeBloc .= '
			<li>
				<h4>Site web :</h4>
				<p><a href="' . $information_site_web . '">' . $information_site_web . '</a></p>
			</li>';
    }
    if (trim($information_mailing)!=='') {
        $codeBloc .= '
			<li>
				<h4>Mailing list :</h4>
				<p>' . $information_mailing . '</p>
			</li>';
    }
    if (trim($information_communaute_utilisateur)!=='') {
        $codeBloc .= '
			<li>
				<h4>Communauté d\'utilisateurs :</h4>
				<p>' . $information_communaute_utilisateur . '</p>
			</li>';
    }
    if (trim($information_entreprises)!=='') {
        $codeBloc .= '
			<li>
				<h4>Entreprises labellisées :</h4>
				<p>' . $information_entreprises . '</p>
			</li>';
    }
    // Si $codeBloc n'est pas vide, cela vaut le coup de générer le code HTML l'englobant.
    if($codeBloc!=='') {
        $codeBloc = '<div class="col">
			<h2 id="titreDescriptifTechnique">Informations</h2>
			<ul>
		' . $codeBloc . '
			</ul>
		</div>';
    }
    return $codeBloc;
}