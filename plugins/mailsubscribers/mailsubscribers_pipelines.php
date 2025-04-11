<?php

/**
 * Plugin mailsubscribers
 * (c) 2012 Cédric Morin
 * Licence GNU/GPL v3
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

if (!defined('_CNIL_PERIODE')) {
	define('_CNIL_PERIODE', 3600 * 24 * 31 * 4);
}

function mailsubscribers_taches_generales_cron($taches) {
	// a peu pres tous les jours mais en se decalant un peu
	$taches['mailsubscribers_synchro_lists'] = 23 * 3600;
	if (isset($GLOBALS['meta']['mailsubscriptions_update_segments'])) {
		$taches['mailsubscribers_update_segments'] = 90;
	}
	// traiter les reconsentements d’abonnements toutes les 15 minutes
	$taches['mailsubscribers_update_optins'] = 15 * 60;

	return $taches;
}

/**
 * Ajouter un jeton unique sur chaque inscrit (sert aux signatures d'action)
 *
 * @param $flux
 * @return mixed
 */
function mailsubscribers_pre_insertion($flux) {
	if (
		$flux['args']['table'] == 'spip_mailsubscribers'
		and !isset($flux['data']['jeton'])
	) {
		include_spip('inc/acces');
		$flux['data']['jeton'] = creer_uniqid();
		include_spip('inc/mailsubscribers');
		if (!isset($flux['data']['email'])) {
			include_spip('inc/acces');
			$flux['data']['email'] = creer_uniqid(); // eviter l'eventuel echec unicite sur email vide
		}
	}

	return $flux;
}

/**
 * Quand le statut de l'abonnement est change, tracer par qui (date, ip, #id si auteur loge, nom/email si en session)
 * Permet d'opposer l'optin d'un internaute a son abonnement
 * (et a contrario de tracer que l'abonnement n'a pas ete fait par lui si c'est le cas...)
 *
 * @param $flux
 * @return mixed
 */
function mailsubscribers_pre_edition($flux) {
	if (
		$flux['args']['table'] == 'spip_mailsubscribers'
		and $id_mailsubscriber = $flux['args']['id_objet']
	) {
		if (
			$flux['args']['action'] == 'instituer'
			and $statut_ancien = $flux['args']['statut_ancien']
			and isset($flux['data']['statut'])
			and $statut = $flux['data']['statut']
			and $statut != $statut_ancien
		) {
			include_spip('inc/mailsubscribers');
			$email = sql_getfetsel('email', 'spip_mailsubscribers', 'id_mailsubscriber=' . intval($id_mailsubscriber));
			// on ne peut jamais passer en prepa, c'est un statut reserve a la creation
			if ($statut == 'prepa' and !autoriser('superinstituer', 'mailsubscriber', $id_mailsubscriber)) {
				unset($flux['data']['statut']);
			}
			// on ne peut jamais passer en prop, c'est un statut intermediaire automatique
			if ($statut == 'prop' and !autoriser('superinstituer', 'mailsubscriber', $id_mailsubscriber)) {
				unset($flux['data']['statut']);
			}
			// on ne peut jamais passer en valide que si on etait en prop
			if (
				$statut == 'valide' and $statut_ancien !== 'prop' and !autoriser(
					'superinstituer',
					'mailsubscriber',
					$id_mailsubscriber
				)
			) {
				unset($flux['data']['statut']);
			} // un subscriber avec email obfusque ne peut que passer en poubelle ou refuse
			elseif (mailsubscribers_test_email_obfusque($email) and !in_array($statut, ['poubelle', 'refuse'])) {
				unset($flux['data']['statut']);
			}
		}
	}


	// changement de mail d'un auteur : faire suivre son inscription si l'adresse email est unique dans les auteurs
	if (
		$flux['args']['table'] == 'spip_auteurs'
		and $id_auteur = $flux['args']['id_objet']
		and isset($flux['data']['email'])
		and $flux['data']['email']
	) {
		$old_email = sql_getfetsel('email', 'spip_auteurs', 'id_auteur=' . intval($id_auteur));
		if (
			$old_email
			and !sql_countsel(
				'spip_auteurs',
				'email=' . sql_quote($old_email) . ' AND id_auteur<>' . intval($id_auteur) . ' AND statut<>' . sql_quote('5poubelle')
			)
		) {
			include_spip('action/editer_objet');
			include_spip('inc/mailsubscribers');
			if (
				$id_mailsubscriber = sql_getfetsel(
					'id_mailsubscriber',
					'spip_mailsubscribers',
					'email=' . sql_quote($old_email)
				)
			) {
				objet_modifier('mailsubscriber', $id_mailsubscriber, ['email' => $flux['data']['email']]);
			}
			if (
				$id_mailsubscriber = sql_getfetsel(
					'id_mailsubscriber',
					'spip_mailsubscribers',
					'email=' . sql_quote(mailsubscribers_obfusquer_email($old_email))
				)
			) {
				objet_modifier(
					'mailsubscriber',
					$id_mailsubscriber,
					['email' => mailsubscribers_obfusquer_email($flux['data']['email'])]
				);
			}
		}
	}

	return $flux;
}

/**
 * Quand le statut de l'abonnement est change, tracer par qui (date, ip, #id si auteur loge, nom/email si en session)
 * Permet d'opposer l'optin d'un internaute a son abonnement
 * (et a contrario de tracer que l'abonnement n'a pas ete fait par lui si c'est le cas...)
 *
 * @param $flux
 * @return mixed
 */
function mailsubscribers_post_edition($flux) {
	if (
		isset($flux['args']['table'])
		and $flux['args']['table'] == 'spip_mailsubscribers'
		and $id_mailsubscriber = $flux['args']['id_objet']
	) {
		if (
			$flux['args']['action'] == 'instituer'
			and $statut_ancien = $flux['args']['statut_ancien']
			and isset($flux['data']['statut'])
			and $statut = $flux['data']['statut']
			and $statut != $statut_ancien
		) {
			include_spip('inc/mailsubscribers');
			$email = sql_getfetsel('email', 'spip_mailsubscribers', 'id_mailsubscriber=' . intval($id_mailsubscriber));
			if (!mailsubscribers_test_email_obfusque($email)) {
				if ($statut == 'valide') {
					$subscriber = charger_fonction('subscriber', 'newsletter');
					$infos = $subscriber($email);
					$add = [];
					foreach ($infos['subscriptions'] as $sub) {
						if ($sub['status'] == 'pending') {
							$add[] = $sub['id'];
						}
					}
					if ($add) {
						$subscribe = charger_fonction('subscribe', 'newsletter');
						$subscribe($email, ['listes' => $add, 'force' => true, 'notify' => false]);
					}
				}

				if (in_array($statut, ['refuse', 'poubelle'])) {
					$unsubscribe = charger_fonction('unsubscribe', 'newsletter');
					$unsubscribe($email, ['notify' => false]);

					$id_job = job_queue_add(
						'mailsubscribers_obfusquer_mailsubscriber',
						"Obfusquer email #$id_mailsubscriber",
						[$id_mailsubscriber],
						'inc/mailsubscribers',
						false,
						time() + 300
					);
					job_queue_link($id_job, ['objet' => 'mailsubscriber', 'id_objet' => $id_mailsubscriber]);
				}
			}
		}
	}

	return $flux;
}


/**
 * Optimiser la base de donnee en supprimant :
 * -> les inscriptions non confirmees
 * -> les inscriptions et les listes a la poubelle
 *
 * @param array $flux
 * @return array
 */
function mailsubscribers_optimiser_base_disparus($flux) {
	$n = &$flux['data'];

	$mydate = sql_quote(trim($flux['args']['date'], "'"));

	# passer en poubelle les inscriptions en attente jamais confirmees (ce sont des bots)
	sql_updateq(
		'spip_mailsubscribers',
		['statut' => 'poubelle', 'date' => date('Y-m-d H:i:s')],
		'statut=' . sql_quote('prepa') . ' AND date < ' . $mydate
	);

	# supprimer les inscrits a la poubelle
	sql_delete('spip_mailsubscribers', 'statut=' . sql_quote('poubelle') . ' AND date < ' . $mydate);

	# supprimer les listes a la poubelle
	sql_delete('spip_mailsubscribinglists', 'statut=' . sql_quote('poubelle') . ' AND date < ' . $mydate . ' AND maj < ' . $mydate);


	# supprimer les inscriptions dont le subscriber n'existe plus
	$res = sql_select(
		'S.id_mailsubscriber AS id',
		'spip_mailsubscriptions AS S
			LEFT JOIN spip_mailsubscribers AS M
				ON M.id_mailsubscriber=S.id_mailsubscriber',
		'M.id_mailsubscriber IS NULL'
	);
	$n += optimiser_sansref('spip_mailsubscriptions', 'id_mailsubscriber', $res);

	# supprimer les inscriptions dont la liste n'existe plus
	$res = sql_select(
		'S.id_mailsubscribinglist AS id',
		'spip_mailsubscriptions AS S
			LEFT JOIN spip_mailsubscribinglists AS L
				ON L.id_mailsubscribinglist=S.id_mailsubscribinglist',
		'L.id_mailsubscribinglist IS NULL'
	);
	$n += optimiser_sansref('spip_mailsubscriptions', 'id_mailsubscribinglist', $res);


	# Une date antérieure à la conservation CNIL
	$date_min = (new \DateTime())
		->sub(DateInterval::createFromDateString(_CNIL_PERIODE . ' seconds'))
		->format('Y-m-d 00:00:00');

	# supprimer les optins (de date dépassée) dont le subscriber n'existe plus
	$res = sql_select(
		'SO.id_mailsubscriptions_optin AS id',
		'spip_mailsubscriptions_optins AS SO
		    LEFT JOIN spip_mailsubscribers AS M
		        ON M.id_mailsubscriber=SO.id_mailsubscriber',
		[
			'M.id_mailsubscriber IS NULL',
			'SO.date < ' . sql_quote($date_min)
		]
	);
	$n += optimiser_sansref('spip_mailsubscriptions_optins', 'id_mailsubscriptions_optin', $res);

	# supprimer les optins (de date dépassée) dont la liste n'existe plus
	$res = sql_select(
		'SO.id_mailsubscriptions_optin AS id',
		'spip_mailsubscriptions_optins AS SO
			LEFT JOIN spip_mailsubscribinglists AS L
				ON L.id_mailsubscribinglist=SO.id_mailsubscribinglist',
		[
			'L.id_mailsubscribinglist IS NULL',
			'SO.date < ' . sql_quote($date_min)
		]
	);
	$n += optimiser_sansref('spip_mailsubscriptions_optins', 'id_mailsubscriptions_optin', $res);


	# reliquat d'inscriptions incoherentes
	// on utilise le critere su.statut=refuse qui est plus rapide que email like '%@example.org'
	$old_sub = sql_allfetsel('su.id_mailsubscriber,su.email', 'spip_mailsubscribers AS su JOIN spip_mailsubscriptions as si on su.id_mailsubscriber=si.id_mailsubscriber', 'su.statut=' . sql_quote('refuse') . ' AND si.id_segment=0 AND si.statut=' . sql_quote('valide'), 'su.id_mailsubscriber', '', '0,50');
	if ($old_sub) {
		$unsubscribe = charger_fonction('unsubscribe', 'newsletter');
		foreach ($old_sub as $sub) {
			// si mail obfusque, on desinscrit de tout
			if (mailsubscribers_test_email_obfusque($sub['email'])) {
				$unsubscribe($sub['email'], ['notify' => false]);
			}
			// sinon on retablit le statut=valide sur le mailsubscriber
			else {
				sql_updateq('spip_mailsubscribers', ['statut' => 'valide'], 'id_mailsubscriber=' . intval($sub['id_mailsubscriber']));
			}
		}
	}

	return $flux;
}

/**
 * Ajout de la coche d'optin sur le formulaire inscription
 *
 * @param array $flux
 * @return array
 */
function mailsubscribers_formulaire_charger($flux) {
	if (
		in_array($flux['args']['form'], ['inscription', 'forum'])
		and is_array($flux['data'])
	) {
		// ici on ne lit pas la config pour aller plus vite (pas grave si on a ajoute le champ sans l'utiliser)
		$flux['data']['mailsubscriber_optin'] = '';
	}

	return $flux;
}

/**
 * Ajout de la coche d'optin sur le formulaire inscription et forum
 *
 * @param array $flux
 * @return array
 */
function mailsubscribers_formulaire_fond($flux) {
	if ($flux['args']['form'] == 'inscription') {
		include_spip('inc/config');
		if (lire_config('mailsubscribers/proposer_signup_optin', 0)) {
			if (preg_match(',</(div|ul)>\s*</fieldset>,Uims', $flux['data'], $m)) {
				$p = strrpos($flux['data'], $m[0]);
				$c = array_merge($flux['args']['contexte'], ['tag' => ($m[1] == 'ul' ? 'li' : 'div')]);
				$input = recuperer_fond('formulaires/inc-optin-subscribe', $c);
				$flux['data'] = substr_replace($flux['data'], $input, $p, 0);
			}
		}
	}
	if ($flux['args']['form'] == 'forum') {
		include_spip('inc/config');
		if (lire_config('mailsubscribers/proposer_comment_optin', 0)) {
			$show = true;
			// si l'utilisateur est connu et deja abonne on propose pas la coche
			if (
				(isset($GLOBALS['visiteur_session']['email']) and $email = $GLOBALS['visiteur_session']['email'])
				or (isset($GLOBALS['visiteur_session']['session_email']) and $email = $GLOBALS['visiteur_session']['session_email'])
			) {
				$newsletter_subscriber = charger_fonction('subscriber', 'newsletter');
				$infos = $newsletter_subscriber($email);
				if ($infos and $infos['status'] == 'on') {
					$show = false;
				}
			}

			if ($show and ($pform = strrpos($flux['data'], '<form')) !== false) {
				if (strrpos($flux['data'], '</textarea>') < $pform) {
					$pform = 0;
				}
				if (
					$p = strripos($flux['data'], '</fieldset>')
					and $pfieldset = strripos($flux['data'], '<fieldset', strlen($flux['data']) - $p)
					and preg_match(',</(div|ul)>\s*</fieldset>,Uims', substr($flux['data'], $pfieldset), $m)
				) {
					$p = strpos($flux['data'], $m[0], $pfieldset);
					$c = array_merge($flux['args']['contexte'], ['tag' => ($m[1] == 'ul' ? 'li' : 'div')]);
					$input = recuperer_fond('formulaires/inc-optin-subscribe', $c);
					$flux['data'] = substr_replace($flux['data'], $input, $p, 0);
				}
			}
		}
	}

	return $flux;
}

/**
 * Reinjecter mailsubscriber_optin dans la previsu forum si besoin
 *
 * @param $flux
 * @return mixed
 */
function mailsubscribers_formulaire_verifier($flux) {
	if (
		$flux['args']['form'] == 'forum'
		and _request('mailsubscriber_optin')
		and isset($flux['data']['previsu'])
	) {
		// reinjecter l'optin dans la previsu
		if ($p = strpos($flux['data']['previsu'], '<input')) {
			$flux['data']['previsu'] = substr_replace(
				$flux['data']['previsu'],
				"<input type='hidden' name='mailsubscriber_optin' value='oui' />",
				$p,
				0
			);
		}
	}

	return $flux;
}

/**
 * Traitement de la coche d'optin sur le formulaire inscription et forum
 *
 * @param array $flux
 * @return array
 */
function mailsubscribers_formulaire_traiter($flux) {
	if (
		$flux['args']['form'] == 'inscription'
		and _request('mailsubscriber_optin')
		and isset($flux['data']['id_auteur'])
		and $id_auteur = $flux['data']['id_auteur']
	) {
		// si on a poste l'optin et auteur inscrit en base
		// verifier quand meme que la config autorise cet optin, et que l'inscription s'est bien faite)
		include_spip('inc/config');
		if (lire_config('mailsubscribers/proposer_signup_optin', 0)) {
			$row = sql_fetsel('nom,email', 'spip_auteurs', 'id_auteur=' . intval($id_auteur));
			if ($row) {
				// inscrire le nom et email
				$newsletter_subscribe = charger_fonction('subscribe', 'newsletter');
				$newsletter_subscribe($row['email'], ['nom' => $row['nom']]);
			}
		}
	}
	if (
		$flux['args']['form'] == 'forum'
		and _request('mailsubscriber_optin')
		and (isset($GLOBALS['visiteur_session']['email']) or isset($GLOBALS['visiteur_session']['session_email']))
	) {
		// si on a poste l'optin et on a un email en session

		// verifier quand meme que la config autorise cet optin, et que l'inscription s'est bien faite)
		include_spip('inc/config');
		if (lire_config('mailsubscribers/proposer_comment_optin', 0)) {
			$email = $nom = '';
			if (isset($GLOBALS['visiteur_session']['email'])) {
				$email = $GLOBALS['visiteur_session']['email'];
			} elseif (isset($GLOBALS['visiteur_session']['session_email'])) {
				$email = $GLOBALS['visiteur_session']['session_email'];
			}
			if (isset($GLOBALS['visiteur_session']['nom'])) {
				$nom = $GLOBALS['visiteur_session']['nom'];
			} elseif (isset($GLOBALS['visiteur_session']['session_nom'])) {
				$nom = $GLOBALS['visiteur_session']['session_nom'];
			}
			if ($email) {
				// inscrire le nom et email
				$newsletter_subscribe = charger_fonction('subscribe', 'newsletter');
				$newsletter_subscribe($email, ['nom' => $nom]);
			}
		}
	}

	return $flux;
}

/**
 * Afficher les inscriptions d'un auteur (et pouvoir les modifier)
 *
 * @param $flux
 * @return mixed
 */
function mailsubscribers_affiche_auteurs_interventions($flux) {
	if ($id_auteur = $flux['args']['id_auteur']) {
		$flux['data'] .= recuperer_fond('prive/squelettes/inclure/auteur-subscription', ['id_auteur' => $id_auteur]);
	}

	return $flux;
}

/**
 * Proteger les formulaires subscriber/unsubscribe
 *
 * @param $formulaires
 * @return array
 */
function mailsubscribers_nospam_lister_formulaires($formulaires) {
	$formulaires[] = 'newsletter_subscribe';
	$formulaires[] = 'newsletter_unsubscribe';

	return $formulaires;
}

function mailsubscribers_affiche_droite($flux) {

	$exec = (empty($flux['args']['exec']) ? '' :  $flux['args']['exec']);
	if (
		$exec === 'mailsubscribinglists'
		or ($exec === 'mailsubscribinglist' and !empty($flux['args']['id_mailsubscribinglist']))
	) {
		$flux['data'] .= recuperer_fond('prive/squelettes/inclure/mailsubscribinglist-optin', $flux['args']);
	}

	return $flux;
}
