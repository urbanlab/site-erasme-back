<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function linkcheck_parcourir($id_branche = null, $timeout = null) {
	// on se donne 20 secondes pour parcourir les tables
	if (is_null($timeout)) {
		$timeout = time() + 20;
	}

	//on regarde si la fonction a déjà été effectuée partiellement en récupérant les ids de reprise
	$dio = lire_config('linkcheck_dernier_id_objet', 0);
	$do = lire_config('linkcheck_dernier_objet', '');
	$etat = lire_config('linkcheck_etat_parcours', '');

	//si le parcours a déja été réalisé, on recommence du début
	if ($etat) {
		ecrire_config('linkcheck_etat_parcours', '');
		ecrire_config('linkcheck_dernier_id_objet', 0);
		ecrire_config('linkcheck_dernier_objet', 0);
	}

	// repartir de la table ou on en était
	$tables_a_traiter = linkcheck_tables_a_traiter();
	$liste_tables = array_keys($tables_a_traiter);
	if (!empty($do)) {
		while (!empty($liste_tables) && reset($liste_tables) !== $do) {
			array_shift($liste_tables);
		}
	}


	foreach ($liste_tables as $table_sql) {
		if ($do !== $table_sql) {
			ecrire_config('linkcheck_dernier_objet', $table_sql);
			ecrire_config('linkcheck_dernier_id_objet', $dio = 0);
		}

		$champs_a_traiter = $tables_a_traiter[$table_sql];

		$primary = id_table_objet($table_sql);
		$select = array_keys($champs_a_traiter);
		$select[] = $primary;
		$select = array_unique($select);
		$select = array_filter($select);

		// Filtrer les les objets dans la base qui contiennent probablement des URLs :
		// on cherche les :// ou -> dans le texte, et les champs de type URL non vide
		$champs_texte = array_keys(array_filter($champs_a_traiter));
		$champs_url = array_diff(array_keys($champs_a_traiter), $champs_texte);
		$having = [];
		if (count($champs_texte)) {
			if (count($champs_texte) > 1) {
				$select[] = 'concat(' . implode(',', $champs_texte) . ') as champs_type_texte';
				$champs_type_texte = 'champs_type_texte';
			} else {
				$champs_type_texte = reset($champs_texte);
			}
			$having[] = "$champs_type_texte LIKE '%://%' OR $champs_type_texte LIKE '%www.%' OR $champs_type_texte LIKE '%->%'";
		}
		if (count($champs_url)) {
			if (count($champs_url) > 1) {
				$select[] = 'concat(' . implode(',', $champs_url) . ') as champs_type_url';
				$champs_type_url = 'champs_type_url';
			} else {
				$champs_type_url = reset($champs_url);
			}
			$having[] = "$champs_type_url != ''";
		}
		$having = (empty($having) ? '' : '(' . implode(' OR ', $having) . ')');

		$where = [];

		// On réduit la recherche à une branche du site
		if ($id_branche > 0) {
			include_spip('inc/rubriques');
			$ids = calcul_branche_in($id_branche);
			$ids = explode(',', $ids);
			$where[] = sql_in('id_rubrique', $ids);
		}

		// TODO : utiliser quete_condition_statut
		$desc = lister_tables_objets_sql($table_sql);
		if (isset($desc['statut'][0]['previsu'])) {
			$statuts = explode(',', str_replace('/auteur', '', $desc['statut'][0]['previsu']));
			foreach ($statuts as $key => $val) {
				if ($val == '!') {
					unset($statuts[$key]);
				}
			}
			if (count($statuts) > 0) {
				$where[] = sql_in('statut', $statuts);
			}
		} elseif (isset($info_table['field']['statut'])) {
			// On exclus de la selection, les objets dont le statut est refuse ou poubelle
			$where[] = sql_in('statut', ['refuse', 'poubelle'], true);
		}

		// iterer par lot pour ne pas exploser ni la mémoire
		$limit = 100;
		do {
			// Recommencer à l'id ou l'on s'est arrêté
			$where[] = $primary . '>' . intval($dio);
			$objets = sql_allfetsel(
				$select,
				$table_sql,
				$where,
				'',
				$primary . ' ASC',
				'0,' . $limit,
				$having
			);
			array_pop($where);
			//pour chaque objet
			$objet = objet_type($table_sql);
			foreach ($objets as $champs) {
				$dio = $id_objet = $champs[$primary];

				//on recense les liens, les champs non déclarés dans le recensement sont ignorés
				$liens = linkcheck_objet_recenser_liens($objet, $id_objet, $champs);

				//on les insere dans la base
				linkcheck_objet_ajouter_liens($objet, $id_objet, $liens);

				if (time() > $timeout) {
					ecrire_config('linkcheck_dernier_id_objet', $id_objet);
					return false;
				}
			}

			//on renseigne les ids de reprise à la fin de chaque lot pour limiter le nombre d'ecritures
			ecrire_config('linkcheck_dernier_id_objet', $dio);

		} while(count($objets));
	}

	//quand la fonction a été executée en entier on renseigne la base
	ecrire_config('linkcheck_etat_parcours', true);
	return true;
}

/**
 * Recenser les liens pour un objet/id_objet
 * @param string $objet
 * @param int $id_objet
 * @return array
 */
function linkcheck_objet_recenser_liens(string $objet, int $id_objet, $champs = null) {
	if (is_null($champs)) {
		$table_sql = table_objet_sql($objet);
		$tables_a_traiter = linkcheck_tables_a_traiter();
		if (empty($tables_a_traiter[$table_sql])) {
			return [];
		}
		$primary = id_table_objet($objet);
		$champs = sql_fetsel('*', $table_sql, "$primary=" . intval($id_objet));
		if (empty($champs)) {
			return [];
		}
	}

	$liens = linkcheck_recenser_liens($objet, $champs);
	spip_log("linkcheck_objet_recenser_liens: $objet #$id_objet " . count($liens) . " lien trouvés", 'linkcheck' . _LOG_DEBUG);
	return $liens;
}

/**
 * Fonction qui liste les liens dans un texte ou un tableau
 *
 * @param string $objet
 *      type de l'objet que l'on traite
 * @param string|array $champs
 * 		Une chaine de caractere ou un tableau de chaine de caractere
 * 		contenant les liens
 * @return array
 * 		La liste des liens trouvé dans la variable $champs
 */
function linkcheck_recenser_liens($objet, $champs) {
	static $fonctions_par_champ = [];
	if (!isset($fonctions_par_champ[$objet])) {
		$table_sql = table_objet_sql($objet);
		$tables_a_traiter = linkcheck_tables_a_traiter();
		$champs_par_objet = $tables_a_traiter[$table_sql] ?? [];

		$fonctions_par_champ[$objet] = [];
		// fonctions de parsing par défaut en fonction du type du champ
		if (
			($f = charger_fonction("linkcheck_recenser_liens_{$objet}_champ_type_url", 'inc', true))
			|| ($f = charger_fonction("linkcheck_recenser_liens_champ_type_url", 'inc'))
		) {
			$fonctions_par_champ[$objet][0] = $f;
		}
		if (
			($f = charger_fonction("linkcheck_recenser_liens_{$objet}_champ_type_texte", 'inc', true))
			|| ($f = charger_fonction("linkcheck_recenser_liens_champ_type_texte", 'inc'))
		) {
			$fonctions_par_champ[$objet][1] = $f;
		}
		foreach ($champs_par_objet as $nom_champ => $type_champ) {
			if (
				($f = charger_fonction("linkcheck_recenser_liens_{$objet}_champ_{$nom_champ}", 'inc', true))
				|| ($f = charger_fonction("linkcheck_recenser_liens_champ_{$nom_champ}", 'inc', true))
				|| ($f = $fonctions_par_champ[$objet][$type_champ])
			) {
				$fonctions_par_champ[$objet][$nom_champ] = $f;
			}
		}
	}

	if (!is_array($champs)) {
		// c'est un texte passé directement, on parse comme un texte pour ce type d'objet
		$liens = $fonctions_par_champ[$objet][1]($champs);
	} else {
		$liens = [];
		foreach ($champs as $nom_champ => $champ_value) {
			if (!empty($fonctions_par_champ[$objet][$nom_champ])) {
				$liens[] = $fonctions_par_champ[$objet][$nom_champ]($champ_value);
			}
		}
		if (!empty($liens)) {
			$liens = array_merge(...$liens);
			$liens = array_unique($liens);
		}
	}

	return $liens;
}


function linkcheck_completer_url_partielle($url_site) {
	if (
		strpos($url_site, 'http://')===0
		|| strpos($url_site, 'https://')===0
	) {
		return $url_site;
	}
	if (
		strpos($url_site, '://') === false
		&& preg_match('#^([a-zA-Z0-9\-]*\.)([a-zA-Z0-9\-]*\.)#', $url_site)
	) {
		$url_site = 'http://' . $url_site;
	}
	return $url_site;
}

function inc_linkcheck_recenser_liens_champ_type_url_dist($champ_value) {
	$liens = [];
	if (!empty($champ_value)) {
		$url_site = trim($champ_value);
		$liens[] = linkcheck_completer_url_partielle($url_site);
	}
	return $liens;
}

function inc_linkcheck_recenser_liens_champ_type_texte_dist($champ_value) {
	static $tab_expreg;
	static $clean_right_preg;

	if (is_null($tab_expreg)) {
		/**
		 * TODO : trouver une regexp mieux que cela et complète
		 * @var string $classe_alpha
		 */
		$classe_alpha = 'a-zA-Z0-9\x{00a1}-\x{FFFF}\(\)';
		$clean_right = ["'", '"', ' ', "\t", "\n", "\r", '.', ',', ';', '|', '->', ')', ']', ':', '?', '~'];
		$clean_right_preg = implode('|', array_map('preg_quote', $clean_right));
		$tab_expreg = [
			"('|\"| |\.|\->|\]|,|;|\s)(((((http|https|ftp|ftps)://)?www\.)|((http|https|ftp|ftps)://(?:\S+(?::\S*)?@)?.([" . $classe_alpha . "'\-]*\.)?))(['" . $classe_alpha . "'0-9\-\+]*\.)+([a-zA-Z0-9]{2,9})(?::\d{2,5})?(/[" . $classe_alpha . "=.?&~_;\-\+\@\:\,/%#]*)?)($clean_right_preg)?",
			'(\->)([a-zA-Z]{3,10}[0-9]{1,})\]'
		];
	}
	$liens = [];
	if (!empty($champ_value)) {
		// ignorer code
		$champ_value = linkcheck_nettoyer_texte($champ_value);
		// trouvé les URLs
		foreach ($tab_expreg as $expreg) {
			if (preg_match_all('`' . $expreg . '`u', ' ' . $champ_value . ' ', $matches) > 0) {
				foreach ($matches[2] as $cle => $m) {
					if (!empty($m) && !in_array(!empty($matches[11][$cle]) ? $matches[11][$cle] : [], ['invalid', 'test', 'localhost', 'example'])) {
						$lien_clean = trim($m);
						while (preg_match("`($clean_right_preg)$`u", $lien_clean, $clean_match)) {
							$lien_clean = trim(substr($lien_clean, 0, -strlen($clean_match[0])));
						}
						$liens[] = $lien_clean;
					}
				}
			}
		}

		if (!empty($liens)) {
			// Ajout du prefix http:// si necessaire
			foreach ($liens as &$url_site) {
				$url_site = trim($url_site);
				$url_site = linkcheck_completer_url_partielle($url_site);
			}
			// Ajout au tableau du lien
			$liens = array_unique($liens);
		}
	}
	return $liens;
}

/**
 * Enlever les blocs de code, cadre, code markdown
 * 
 * @param string $texte
 * @return string
 */
function linkcheck_nettoyer_texte(string $texte): string {
	static $wheel = null;

	// code, cadre, ...
	$texte = echappe_html($texte, 'TYPO');

	// backtick markdowns
	if ($wheel === null) {
		include_spip('inc/textwheel');
		if (class_exists('Textwheel')) {
			$wheel = new TextWheel(
				SPIPTextWheelRuleset::loader($GLOBALS['spip_wheels']['pre_echappe_html_propre'] ?? [])
			);				
		} else {
			$wheel = false;
		}
	}
	if ($wheel instanceof Textwheel) {
		try {
			$texte = $wheel->text($texte);
		} catch (Exception $e) {
			// tant pis…
		}		
	}

	return $texte;
}