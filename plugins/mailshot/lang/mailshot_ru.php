<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// extrait automatiquement de https://trad.spip.net/tradlang_module/mailshot?lang_cible=ru
// ** ne pas modifier le fichier **

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// C
	'cfg_exemple' => 'Пример',
	'cfg_exemple_explication' => 'Описание примера',
	'cfg_titre_parametrages' => 'Настройка массовой рассылки',

	// E
	'erreur_aucun_service_configure' => 'Серсис рассылки не настроен . <a Href="@url@"> настроить рассылку < / a>',
	'erreur_envoi_mail_bloque_debug' => 'Отправка емейл заблокирована <tt>_TEST_EMAIL_DEST</tt>',
	'erreur_envoi_mail_force_debug' => 'Принудительная отправка электронной почты на адрес @email@ началась <tt>_TEST_EMAIL_DEST</tt>',
	'erreur_envoi_newsletter' => 'При отправке рассылки произошла неизвестная ошибка.',
	'erreur_generation_newsletter' => 'Произошла ошибка при создании рассылки.',
	'explication_boost_send' => 'В этом режиме электронные письма будут отправлены как можно быстрее - не учитывается ограничение на частоту отправок.
Быстрая отправка не рекомендуется, потому что это увеличивает риск попадания писем в СПАМ.',
	'explication_purger_historique' => 'Для каждой массовой отправки  в базе данных хранится вся информация о получателях с информацией о состоянии отправки.
Это может занимать большой объем данных и рекомендуется очистить детали старых рассылок.',
	'explication_rate_limit' => 'Укажите максимальное количество писем, посланных в день или оставьте поле пустым если нет каких-либо ограничений',

	// I
	'info_1_mailshot' => '1 запись',
	'info_1_mailshot_destinataire' => '1 получатель',
	'info_1_mailsubscriber' => '1 получатель',
	'info_annuler_envoi' => 'Отменить отправку',
	'info_archiver' => 'Архивировать',
	'info_aucun_destinataire' => 'Нет получателей',
	'info_aucun_envoi' => 'В данный момент, рассылка не осуществляется.',
	'info_envoi_programme_1_destinataire' => 'Отправка запланирована одному получателю',
	'info_envoi_programme_nb_destinataires' => 'Отправка запланирована @nb@ получателям',
	'info_mailshot_no' => 'Отправка No. @id@',
	'info_nb_mailshots' => '@nb@ записей',
	'info_nb_mailshots_destinataires' => '@nb@ получателей',
	'info_nb_mailsubscribers' => '@nb@ получателей',
	'info_statut_archive' => 'архив',
	'info_statut_cancel' => 'Отменено',
	'info_statut_destinataire_clic' => 'Кликнуто',
	'info_statut_destinataire_fail' => 'Ошибка',
	'info_statut_destinataire_kill' => 'Отменено',
	'info_statut_destinataire_read' => 'Открыть',
	'info_statut_destinataire_sent' => 'Отправлено',
	'info_statut_destinataire_spam' => '> Спам',
	'info_statut_destinataire_todo' => 'Отправить',
	'info_statut_end' => 'Окончено',
	'info_statut_init' => 'Запланировано',
	'info_statut_pause' => 'Пауза',
	'info_statut_poubelle' => 'Корзина',
	'info_statut_processing' => 'В процессе...',

	// L
	'label_avancement' => 'Ход',
	'label_boost_send_oui' => 'Быстрая отправка',
	'label_control_pause' => 'Пауза',
	'label_control_play' => 'Начать заново',
	'label_control_stop' => 'Отменить',
	'label_date_fin' => 'Дата окончания отправки',
	'label_date_start' => 'Дата отправки',
	'label_envoi' => 'Отправка',
	'label_from' => 'Отправитель',
	'label_graceful' => 'Только получатели, которым этот контент еще не был доставлен',
	'label_html' => 'Текст рассылки HTML',
	'label_listes' => 'Базы',
	'label_mailer_defaut' => 'Использовать такой же сервис отправки как и для других емейлов',
	'label_mailer_defaut_desactive' => 'Ошибка : служба отпраки электронной почты не настроена',
	'label_mailer_mailjet' => 'Mailjet',
	'label_mailer_mandrill' => 'Mandrill Service',
	'label_mailer_smtp' => 'SMTP Сервер',
	'label_mailer_sparkpost' => 'Sparkpost',
	'label_mailjet_api_key' => 'API Mailjet Key',
	'label_mailjet_api_version' => 'Версия API ',
	'label_mailjet_secret_key' => 'Секретный ключ Mailjet',
	'label_mandrill_api_key' => 'Mandrill API Key',
	'label_purger_historique_delai' => 'Старше чем',
	'label_purger_historique_oui' => 'Удлаить информацию о старых отправках',
	'label_rate_limit' => 'Предельная скорость передачи',
	'label_sparkpost_api_endpoint' => 'API Endpoint',
	'label_sparkpost_api_key' => 'Sparkpost API Key',
	'label_sujet' => 'Тема',
	'label_texte' => 'Текст рассылки',
	'legend_configuration_adresse_envoi' => 'Адрес доставки',
	'legend_configuration_historique' => 'История отправок',
	'legend_configuration_mailer' => 'Сервис для отправки почты',
	'lien_voir_newsletter' => 'Смотреть рассылку',

	// M
	'mailshot_titre' => 'MailShot',

	// T
	'texte_changer_statut_mailshot' => 'Этот елемент :',
	'texte_statut_archive' => 'архив',
	'texte_statut_cancel' => 'отменено',
	'texte_statut_end' => 'окончено',
	'texte_statut_init' => 'запланировано',
	'texte_statut_pause' => 'остановлено',
	'texte_statut_processing' => 'В процессе...',
	'titre_envois_archives' => 'Архив рассылок',
	'titre_envois_destinataires_clic' => 'Клики в письмах',
	'titre_envois_destinataires_fail' => 'Не удалось отправить',
	'titre_envois_destinataires_init_encours' => 'Получатель не создан (инициализация в процессе)',
	'titre_envois_destinataires_ok' => 'Доставлено',
	'titre_envois_destinataires_read' => 'Открытые письма',
	'titre_envois_destinataires_sent' => 'Доставлено',
	'titre_envois_destinataires_spam' => 'Письма и СПАМ',
	'titre_envois_destinataires_todo' => 'Следующие  рассылки',
	'titre_envois_en_cours' => 'В процессе отправки...',
	'titre_envois_planifies' => 'Запланированные отправки',
	'titre_envois_termines' => 'Отправлено',
	'titre_mailshot' => 'Масовая рассылка',
	'titre_mailshots' => 'Массовые рассылки',
	'titre_menu_mailshots' => 'Настройка рассылки',
	'titre_page_configurer_mailshot' => 'Mailshot'
);
