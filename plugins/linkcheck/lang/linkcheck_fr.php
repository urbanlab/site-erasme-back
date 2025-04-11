<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// Fichier source, a modifier dans https://git.spip.net/spip-contrib-extensions/linkcheck.git
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// A
	'afficher_alerte' => 'Afficher les alertes',
	'analyser_site_linkchecks' => 'Analyser les liens de mon site',

	// B
	'bouton_exporter_selection' => 'Exporter la sélection en CSV',
	'bouton_modifier_lien' => 'Mettre à jour ce lien',
	'bouton_tester_lien' => 'Tester ce lien',

	// C
	'commencer_parcours' => 'Commencer la recherche',
	'commencer_verification' => 'Les liens ont été trouvés, appuyer sur le bouton pour la vérification de ces liens',
	'commencer_verification_liens' => 'Commencer la vérification',
	'configuration_generale' => 'Configuration générale',
	'configurer_linkcheck' => 'Configurer LinkCheck',
	'continuer_parcours' => 'Continuer la recherche',
	'continuer_parcours_explication' => 'Continuer le parcours du site à la recherche des liens',

	// D
	'descriptif' => 'Ce plugin vérifie, en tâche de fond, si les liens présents dans les objets SPIP de votre site, pointent toujours vers des ressources en ligne.',
	'distant_non' => 'Liens internes',
	'distant_oui' => 'Liens externes',

	// E
	'etat' => 'État',
	'etat_' => 'Inconnu',
	'etat_deplace' => 'Déplacé',
	'etat_malade' => 'Malade',
	'etat_mort' => 'Mort',
	'etat_ok' => 'Valide',
	'etat_restreint' => 'Restreint',

	// I
	'id' => 'ID',
	'info_1_a_parcourir' => '1 objet à parcourir',
	'info_1_linkcheck' => 'Un lien',
	'info_aucun_lien' => 'Aucun lien n’est encore recensé.',
	'info_aucun_linkcheck' => 'Aucun lien détecté',
	'info_aucun_objet_a_parcourir' => 'Aucun objet à parcourir',
	'info_dont_non_verifie' => 'dont <span id="nb_lien_inconnu">@nb@</span> non-verifié',
	'info_dont_non_verifies' => 'dont <span id="nb_lien_inconnu">@nb@</span> non-verifiés',
	'info_nb_linkchecks' => '@nb@ liens',
	'info_nb_objets_a_parcourir' => '@nb@ objets à parcourir',
	'info_parcours_en_cours' => 'Recherche de liens en cours (@progress@%)',
	'info_parcours_todo' => 'Aucun parcours en cours',

	// L
	'label_activer_linkcheck_objets' => 'Activer la vérification de liens sur les objets',
	'label_code' => 'Code',
	'label_traiter_propre' => 'Désactiver automatiquement les liens morts à l’affichage',
	'lien' => 'lien',
	'lien_' => 'Lien non-verifié',
	'lien_deplace' => 'Lien déplacé',
	'lien_malade' => 'Lien malade',
	'lien_mort' => 'Lien mort',
	'lien_ok' => 'Lien valide',
	'lien_restreint' => 'Lien restreint',
	'liens' => 'liens',
	'liens_deplaces' => 'Liens déplacés',
	'liens_invalides' => 'Il y a des liens invalides dans le contenu de votre site (Mort : @mort@ / Malade : @malade@ / Déplacé : @deplace@) !',
	'liens_malades' => 'Liens malades',
	'liens_morts' => 'Liens morts',
	'liens_oks' => 'Liens validés',
	'liens_publies' => 'Visibles en ligne',
	'liens_publies_non' => 'Non visibles en ligne',
	'liens_restreints' => 'Liens restreints',
	'linkcheck' => 'LinkCheck',
	'linkcheck_menu' => 'Vérifier les liens',
	'liste_des_liens' => 'Listes des liens',

	// M
	'mail_notification1' => 'Bonjour,<br/>
							Vous recevez ce mail car certains liens présents sur votre site posent problème.<br/>
							Veuillez trouver ci-dessous, un récapitulatif : <br/>',
	'mail_notification2' => 'Veuillez vous rendre sur l’administration de votre site afin de résoudre ces problèmes ! Merci.',
	'maj' => 'Mis à jour ',
	'message_confirmation_reinitialiser' => 'Êtes-vous sûres de vouloir vider les tables spip_linkchecks et spip_linkchecks_liens',

	// N
	'notifier_par_courriel' => 'Notifier par courriel',

	// O
	'ouvrenouvelonglet' => 'nouvel onglet',

	// P
	'parcours_incomplete' => 'Le parcours des liens du site est incomplet ou n’a pas encore été effectué !',
	'pas_encore_de_liens' => 'Pour lancer la vérification des liens, il vous faut lancer la fonction qui va parcourir le site à la recherche de tous les liens. Suivant la taille de votre site internet, l’opération de recherche peut durer entre quelques secondes à quelques minutes. Si la page ne répond pas, recharger simplement cette page.',
	'poursuivre_verification' => 'Tous les liens ne sont pas encore vérifiés, il vous faut poursuivre la vérification en cliquant sur ce bouton',
	'poursuivre_verification_liens' => 'Poursuivre la vérification',

	// R
	'redirect_oui' => 'Redirigés',
	'redirection' => 'Redirection',
	'reinitialiser' => 'Réinitialiser',
	'reinitialiser_la_base' => 'Vous souhaitez refaire toutes les opérations de recherche et de vérification des liens, vous pouvez le faire en appuyant sur le bouton "Réinitialiser". Cette action videra les tables spip_linkchecks et spip_linkchecks_liens',
	'rescensement_incomplet' => 'Le recensement des liens du site est incomplet ! ',

	// T
	'tester_liens_linkchecks' => 'Tester les liens de mon site',
	'titre_linkcheck' => 'Vérificateur de liens',
	'titre_linkchecks' => 'Vérificateur de liens',
	'titre_page_configurer' => 'Configurer le plugin Linkcheck',
	'tous_les_liens' => 'Tous',

	// U
	'url' => 'URL',
	'utilise' => 'Utilisé dans '
);
