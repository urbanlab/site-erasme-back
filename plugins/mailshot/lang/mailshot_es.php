<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/mailshot?lang_cible=es
// ** ne pas modifier le fichier **

return [

	// B
	'bouton_acquiter_alerte' => 'Reactivar los envíos',

	// C
	'cfg_exemple' => 'Ejemplo',
	'cfg_exemple_explication' => 'Explicación de este ejemplo',
	'cfg_titre_parametrages' => 'Configurar el envío de correos electrónicos masivos',

	// E
	'erreur_aucun_service_configure' => 'No hay servicio de envío configurado. <a href="@url@">Configurar un servicio</a>',
	'erreur_envoi_mail_bloque_debug' => 'Envío de correo electrónico bloqueado por <tt>_TEST_EMAIL_DEST</tt>.',
	'erreur_envoi_mail_force_debug' => 'Correo electrónico forzado enviado a @email@ por <tt>_TEST_EMAIL_DEST</tt>.',
	'erreur_envoi_newsletter' => 'Ocurrió un error desconocido al enviar el boletín.',
	'erreur_generation_newsletter' => 'Ocurrió un error al generar el boletín.',
	'explication_boost_send' => 'En este modo, los correos se enviarán lo más rápido posible. No se tiene en cuenta ningún límite de tasa.
No se recomienda el envío rápido porque aumenta el riesgo de clasificación como SPAM.',
	'explication_check_fail_ratio' => 'Si la tasa de error supera el @ratio@%, los envíos se suspenderán y se enviará un correo electrónico al webmaster para verificar el servicio de envío.',
	'explication_purger_historique' => 'Para cada envío masivo, todos los destinatarios se mantienen en la base de datos, con información sobre el estado de su envío.
Esto puede representar un gran volumen de datos si realiza muchos envíos y es recomendable depurar los detalles de los envíos antiguos.',
	'explication_rate_limit' => 'Indique el número máximo de correos electrónicos enviados por día o déjelo en blanco para no establecer un límite',

	// I
	'info_1_mailshot' => '1 envio',
	'info_1_mailshot_destinataire' => '1 destinatario',
	'info_1_mailsubscriber' => '1 inscrito',
	'info_annuler_envoi' => 'Cancelar enviar',
	'info_archiver' => 'Archivo',
	'info_aucun_destinataire' => 'Sin destinatario',
	'info_aucun_envoi' => 'Sin envío',
	'info_date_envoi' => 'Fecha de envío:',
	'info_envoi_programme_1_destinataire' => 'Envío programado a 1 destinatario',
	'info_envoi_programme_nb_destinataires' => 'Envío programado a @nb@ destinatarios',
	'info_mailshot_no' => 'Envio N°@id@',
	'info_nb_mailshots' => '@nb@ envíos',
	'info_nb_mailshots_destinataires' => '@nb@ destinatarios',
	'info_nb_mailsubscribers' => '@nb@ inscritos',
	'info_statut_archive' => 'Archivo',
	'info_statut_cancel' => 'Cancelado',
	'info_statut_destinataire_clic' => 'Haz clic',
	'info_statut_destinataire_fail' => 'Fallido',
	'info_statut_destinataire_kill' => 'Anular',
	'info_statut_destinataire_read' => 'Abierto',
	'info_statut_destinataire_sent' => 'Enviado',
	'info_statut_destinataire_spam' => '>Spam',
	'info_statut_destinataire_todo' => 'Enviar',
	'info_statut_end' => 'Finalizar',
	'info_statut_init' => 'Planificado',
	'info_statut_pause' => 'Pausa',
	'info_statut_poubelle' => 'A la papelera',
	'info_statut_processing' => 'En curso',

	// L
	'label_avancement' => 'Avance',
	'label_boost_send_oui' => 'Envío rápido',
	'label_check_fail_ratio_oui' => 'Monitoreo de la tasa de fallas',
	'label_control_pause' => 'Pausar',
	'label_control_play' => 'Reiniciar',
	'label_control_stop' => 'Abandonar',
	'label_date_fin' => 'Fecha final del envío',
	'label_date_start' => 'Fecha de inicio de envío',
	'label_envoi' => 'Correo',
	'label_from' => 'Remitente',
	'label_graceful' => 'Solo destinatarios que aún no han recibido este contenido',
	'label_html' => 'En versión HTML',
	'label_listes' => 'Listas',
	'label_mailer_defaut' => 'Use el mismo servicio de envío que para otros correos electrónicos',
	'label_mailer_defaut_desactive' => 'Imposible: No hay servicio de envío de correo electrónico configurado',
	'label_mailer_mailjet' => 'Mailjet',
	'label_mailer_mandrill' => 'Mandrill',
	'label_mailer_sendinblue' => 'Sendinblue', # MODIF
	'label_mailer_smtp' => 'Servidor SMTP',
	'label_mailer_sparkpost' => 'Sparkpost',
	'label_mailjet_api_key' => 'Clave API de Mailjet',
	'label_mailjet_api_version' => 'Versión API ',
	'label_mailjet_secret_key' => 'Clave secreta de Mailjet',
	'label_mandrill_api_key' => 'Clave API de Mandrill',
	'label_purger_historique_delai' => 'Mayor que',
	'label_purger_historique_oui' => 'Eliminar los detalles de envíos antiguos',
	'label_rate_limit' => 'Limitar la tasa de envío',
	'label_sendinblue_api_key' => 'Clave APIv3 de Sendinblue', # MODIF
	'label_sparkpost_api_endpoint' => 'API Endpoint',
	'label_sparkpost_api_key' => 'Clave API Sparkpost',
	'label_sujet' => 'Asunto',
	'label_texte' => 'Versión Texto',
	'legend_configuration_adresse_envoi' => 'Dirección de envío',
	'legend_configuration_historique' => 'Historial de envíos',
	'legend_configuration_mailer' => 'Servicio de envío de correo electrónico',
	'lien_voir_newsletter' => 'Ver el boletín',

	// M
	'mail_alerte_fail_ratio_sujet' => '[Mailshot #@id@] ALERTA tasa de fallas en el envío de correos electrónicos',
	'mail_alerte_fail_ratio_texte' => 'Se ha detectado una tasa de falla anormal de @ratio@% en el envío de correos masivos #@id@. Todos los envíos están en pausa. Conéctese al sitio @url@ para verificar su servicio de envío.',
	'mailshot_titre' => 'MailShot',
	'message_admin_alerte_fail_ratio' => 'El @date@ se detectó una tasa de error anormal en el envío de correos electrónicos masivos. <br />Todos los envíos están suspendidos. <br />Verifique su servicio de envío antes de volver a habilitar el envío.',

	// T
	'texte_changer_statut_mailshot' => 'Este envío está:',
	'texte_statut_archive' => 'archivado',
	'texte_statut_cancel' => 'cancelado',
	'texte_statut_end' => 'terminado',
	'texte_statut_init' => 'planificado',
	'texte_statut_pause' => 'en pausa',
	'texte_statut_processing' => 'en curso',
	'titre_envois_archives' => 'Envíos archivados',
	'titre_envois_destinataires_clic' => 'Correos electrónicos en los que se hizo clic',
	'titre_envois_destinataires_fail' => 'Envíos fallidos',
	'titre_envois_destinataires_init_encours' => 'Ningún destinatario programado (inicializar en curso)',
	'titre_envois_destinataires_kill' => 'Envíos cancelados',
	'titre_envois_destinataires_ok' => 'Envíos exitosos',
	'titre_envois_destinataires_read' => 'Correos electrónicos abiertos',
	'titre_envois_destinataires_sent' => 'Envíos exitosos',
	'titre_envois_destinataires_spam' => 'Correos electrónicos en el Spam',
	'titre_envois_destinataires_todo' => 'Próximos envíos',
	'titre_envois_en_cours' => 'Envíos en curso',
	'titre_envois_planifies' => 'Envíos programados',
	'titre_envois_termines' => 'Envíos completados',
	'titre_mailshot' => 'Envío masivo',
	'titre_mailshots' => 'Envíos masivos',
	'titre_menu_mailshots' => 'Seguimiento de envíos masivos',
	'titre_page_configurer_mailshot' => 'Configurar MailShot',
];
