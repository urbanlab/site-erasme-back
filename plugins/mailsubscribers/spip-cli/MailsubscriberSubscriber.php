<?php

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressHelper;

class MailsubscriberSubscriber extends Command {
	protected function configure() {
		$this
			->setName('mailsubscriber:subscriber')
			->setDescription('Afficher les informations d\'un subscriber')
			->addOption(
				'email',
				null,
				InputOption::VALUE_REQUIRED,
				'email a inscrire',
				null
			)
			->addOption(
				'listes',
				null,
				InputOption::VALUE_OPTIONAL,
				'Listes (separées par des virgules si plusieurs)',
				null
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		include_spip('inc/filtres');

		$email = $input->getOption('email');
		if (!$email) {
			$output->writeln('<error>Indiquez un email</error>');
			return self::FAILURE;
		}
		if (!$email = email_valide($email)) {
			$output->writeln('<error>Indiquez un email valide</error>');
			return self::FAILURE;
		}

		$options = [];
		$listes = $input->getOption('listes');
		if (!is_null($listes)) {
			$options['listes'] = explode(',', $listes);
		}

		$subscriber = charger_fonction('subscriber', 'newsletter');
		$infos = $subscriber($email, $options);

		$output->writeln(var_export($infos, true));

		if ($infos) {
			// afficher aussi les informations liees
			if (!function_exists('mailsubscriber_declarer_informations_liees')) {
				include_spip('inc/mailsubscribers');
			}
			if ($declaration = mailsubscriber_declarer_informations_liees()) {
				$id_mailsubscriber = sql_getfetsel('id_mailsubscriber', 'spip_mailsubscribers', 'email=' . sql_quote($infos['email']));
				$infos_liees = mailsubscriber_recuperer_informations_liees($id_mailsubscriber, $infos['email']);
				$output->writeln("\nInformations liees :");
				$output->writeln(var_export($infos_liees, true));
			}
		}

		return self::SUCCESS;
	}
}
