<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/mailshot?lang_cible=en
// ** ne pas modifier le fichier **

return [

	// B
	'bouton_acquiter_alerte' => 'Restart sendings', # RELIRE

	// C
	'cfg_exemple' => 'Example',
	'cfg_exemple_explication' => 'Explanation of this example',
	'cfg_titre_parametrages' => 'Configure the bulk mailer',

	// E
	'erreur_aucun_service_configure' => 'No bulk mailing service configured. <a Href="@url@"> Configure a service</a>', # RELIRE
	'erreur_envoi_mail_bloque_debug' => 'Email sending blocked by <tt>_TEST_EMAIL_DEST</tt>', # RELIRE
	'erreur_envoi_mail_force_debug' => 'Email sending forced to @email@ by <tt>_TEST_EMAIL_DEST</tt>', # RELIRE
	'erreur_envoi_newsletter' => 'An unknown error occurred while sending the newsletter.', # RELIRE
	'erreur_generation_newsletter' => 'An error occurred while generating the newsletter.', # RELIRE
	'explication_boost_send' => 'In this mode mails are being sent as fast as possible. No sending rate is taken into account.
										This mode is not recommended as it increases the risk of being classified as SPAM.', # RELIRE
	'explication_check_fail_ratio' => 'If the failure rate exceeds @ratio@% sendings will be paused and an email will be sent to the webmaster to check the mailing service', # RELIRE
	'explication_purger_historique' => 'For each bulk mailing, recipients are saved in the database together with their sending status.
	This may cause a high volume of data in case of large mailing lists, and it’s recommended to purge details of older sendings.', # RELIRE
	'explication_rate_limit' => 'Specify the maximum number of mails sent per day, or leave blank to set no limit',

	// I
	'info_1_mailshot' => '1 sending', # RELIRE
	'info_1_mailshot_destinataire' => '1 recipient', # RELIRE
	'info_1_mailsubscriber' => '1 subscriber',
	'info_annuler_envoi' => 'Cancel the sending', # RELIRE
	'info_archiver' => 'Archive',
	'info_aucun_destinataire' => 'No recipient',
	'info_aucun_envoi' => 'No sending',
	'info_date_envoi' => 'Sending date:', # RELIRE
	'info_envoi_programme_1_destinataire' => 'Sending scheduled to one recipient',
	'info_envoi_programme_nb_destinataires' => 'Sending scheduled to @nb@ recipients',
	'info_mailshot_no' => 'Sending No. @id@',
	'info_nb_mailshots' => '@nb@ sendings', # RELIRE
	'info_nb_mailshots_destinataires' => '@nb@ recipients',
	'info_nb_mailsubscribers' => '@nb@ subscribers',
	'info_statut_archive' => 'Archived', # RELIRE
	'info_statut_cancel' => 'Canceled',
	'info_statut_destinataire_clic' => 'Clicked', # RELIRE
	'info_statut_destinataire_fail' => 'Failed', # RELIRE
	'info_statut_destinataire_kill' => 'Canceled',
	'info_statut_destinataire_read' => 'Open',
	'info_statut_destinataire_sent' => 'Sent',
	'info_statut_destinataire_spam' => '>Spam', # RELIRE
	'info_statut_destinataire_todo' => 'To send',
	'info_statut_end' => 'Finished',
	'info_statut_init' => 'Planned',
	'info_statut_pause' => 'Pause',
	'info_statut_poubelle' => 'Trash',
	'info_statut_processing' => 'In progress',

	// L
	'label_avancement' => 'Progress',
	'label_boost_send_oui' => 'Fast sending', # RELIRE
	'label_check_fail_ratio_oui' => 'Failure rate monitoring', # RELIRE
	'label_control_pause' => 'Pause',
	'label_control_play' => 'Restart',
	'label_control_stop' => 'Abort',
	'label_date_fin' => 'Sending end date', # RELIRE
	'label_date_start' => 'Sending start date', # RELIRE
	'label_envoi' => 'Sending',
	'label_from' => 'Sender',
	'label_graceful' => 'Only recipients who have not already received this content', # RELIRE
	'label_html' => 'HTML Version',
	'label_listes' => 'Lists',
	'label_mailer_defaut' => 'Use the same mailing service as for other mails', # RELIRE
	'label_mailer_defaut_desactive' => 'Failed : no mailing service configured yet', # RELIRE
	'label_mailer_mailjet' => 'Mailjet',
	'label_mailer_mandrill' => 'Mandrill Service',
	'label_mailer_sendinblue' => 'Sendinblue', # MODIF
	'label_mailer_smtp' => 'SMTP Server',
	'label_mailer_sparkpost' => 'Sparkpost',
	'label_mailjet_api_key' => 'Mailjet API key', # RELIRE
	'label_mailjet_api_version' => 'API Version',
	'label_mailjet_secret_key' => 'Mailjet secret key', # RELIRE
	'label_mandrill_api_key' => 'Mandrill API Key',
	'label_purger_historique_delai' => 'Older than',
	'label_purger_historique_oui' => 'Purge details of old distributions',
	'label_rate_limit' => 'Limit sending rate',
	'label_sendinblue_api_key' => 'Sendinblue APIv3 Key', # MODIF
	'label_sparkpost_api_endpoint' => 'API Endpoint',
	'label_sparkpost_api_key' => 'Sparkpost API Key',
	'label_sujet' => 'Subject',
	'label_texte' => 'Text Version',
	'legend_configuration_adresse_envoi' => 'Sender email address', # RELIRE
	'legend_configuration_historique' => 'Sending history', # RELIRE
	'legend_configuration_mailer' => 'Mails sending service', # RELIRE
	'lien_voir_newsletter' => 'View Newsletter',

	// M
	'mail_alerte_fail_ratio_sujet' => '[Sending #@id@] ALERT Sendings failure rate', # RELIRE
	'mail_alerte_fail_ratio_texte' => 'An unusual failure rate of @ratio@% has been detected on the sending #@id@. All sendings are paused. Please connect to the @url@ website to check your sending service.', # RELIRE
	'mailshot_titre' => 'MailShot',
	'message_admin_alerte_fail_ratio' => 'On @date@ an unusual failure rate has been detected on bulk mailings. <br />All sendings are paused. <br />Check your sending service before restarting sendings', # RELIRE

	// T
	'texte_changer_statut_mailshot' => 'This sending is:', # RELIRE
	'texte_statut_archive' => 'archived',
	'texte_statut_cancel' => 'canceled',
	'texte_statut_end' => 'finished',
	'texte_statut_init' => 'planned',
	'texte_statut_pause' => 'paused',
	'texte_statut_processing' => 'in progress',
	'titre_envois_archives' => 'Archived sendings', # RELIRE
	'titre_envois_destinataires_clic' => 'Clicked mails', # RELIRE
	'titre_envois_destinataires_fail' => 'Sending failed', # RELIRE
	'titre_envois_destinataires_init_encours' => 'No recipient yet (initialization in progress)', # RELIRE
	'titre_envois_destinataires_kill' => 'Canceled sendings', # RELIRE
	'titre_envois_destinataires_ok' => 'Successful sendings', # RELIRE
	'titre_envois_destinataires_read' => 'Open mails', # RELIRE
	'titre_envois_destinataires_sent' => 'Successful sendings', # RELIRE
	'titre_envois_destinataires_spam' => 'Spam mails', # RELIRE
	'titre_envois_destinataires_todo' => 'To be sent', # RELIRE
	'titre_envois_en_cours' => 'Sendings in progress', # RELIRE
	'titre_envois_planifies' => 'Scheduled sendings', # RELIRE
	'titre_envois_termines' => 'Sendings completed', # RELIRE
	'titre_mailshot' => 'Bulk mailing',
	'titre_mailshots' => 'Bulk mailings',
	'titre_menu_mailshots' => 'Bulk mailings status', # RELIRE
	'titre_page_configurer_mailshot' => 'Mailshot',
];
