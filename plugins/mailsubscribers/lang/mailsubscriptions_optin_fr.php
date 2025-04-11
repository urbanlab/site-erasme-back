<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// Fichier source, a modifier dans https://git.spip.net/spip-contrib-extensions/mailsubscribers.git
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// B
	'bouton_envoyer_demande_optin' => 'Envoyer une demande d’optin à ces @nb@ inscrits',

	// I
	'info_1_mailsubscriptions_optin' => '1 demande d’opt-in',
	'info_aucun_mailsubscriptions_optin' => 'Aucune demande d’opt-in',
	'info_consent_au_moins_nb_envois_sur_nb_mois' => 'Il faut au moins @nb_envois@ envois effectués dans les @nb_mois@ derniers mois pour vérifier les inscrits',
	'info_consent_parmi_inscrits_ayant_recu_moitie' => 'Parmi les inscrits ayant reçus au moins la moitié des envois en un an',
	'info_consent_sur_un_an' => 'Sur 1 an :',
	'info_nb_mailsubscriptions_optin' => '@nb@ demandes d’opt-in',
	'info_page_desinscription' => 'Si un inscrit ne répond pas positivement à la demande d’optin envoyée au bout de 7 jours, il sera désinscrit de la liste',
	'info_page_legend' => 'Confirmation d’intérêt',
	'info_page_lien' => 'Accéder à la gestion d’opt-in',
	'info_page_prestataire' => 'Cela ne fonctionne qu’avec un prestataire d’envoi de courriels collectant des informations statistiques de lecture (donc pas via un <code>SMTP</code>, ni <code>mail()</code> de PHP)',
	'info_page_texte' => 'Vous pouvez demander à vos abonnés, après un certain nombre d’envoi a priori sans lecture de leur part sur une liste, une confirmation de leur volonté de réceptionner ces courriels.',
	'info_page_titre' => 'Demandes d’opt-in',
	'info_statut_outdated' => 'Demande d’optin expirée',
	'info_statut_outdated_short_1' => 'expirée',
	'info_statut_outdated_short_nb' => 'expirées',
	'info_statut_prepa' => 'Demande d’optin à envoyer',
	'info_statut_prepa_short_1' => 'à envoyer',
	'info_statut_prepa_short_nb' => 'à envoyer',
	'info_statut_prop' => 'Demande d’optin envoyée',
	'info_statut_prop_short_1' => 'envoyée',
	'info_statut_prop_short_nb' => 'envoyées',
	'info_statut_refuse' => 'Demande d’optin refusée',
	'info_statut_refuse_short_1' => 'refusée',
	'info_statut_refuse_short_nb' => 'refusées',
	'info_statut_valide' => 'Demande d’optin acceptée',
	'info_statut_valide_short_1' => 'acceptée',
	'info_statut_valide_short_nb' => 'acceptées',

	// S
	'selection_mailinglist' => 'Veuillez sélectionner une liste de diffusion.',

	// T
	'titre_inscrits_sans_demande_optin' => 'Inscrits sans demande d’optin'
);
