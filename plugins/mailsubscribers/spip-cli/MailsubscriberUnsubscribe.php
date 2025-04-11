<?php

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressHelper;

class MailsubscriberUnsubscribe extends Command {
	protected function configure() {
		$this
			->setName('mailsubscriber:unsubscribe')
			->setDescription('Desinscrire un email a des listes de diffusion')
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
			->addOption(
				'notify',
				null,
				InputOption::VALUE_OPTIONAL,
				'indique si on veut ou non notifier par email',
				true
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
		foreach (['notify'] as $o) {
			if (!is_null($value = $input->getOption($o))) {
				$options[$o] = $value;
			}
		}

		$listes = $input->getOption('listes');
		if (!is_null($listes)) {
			$options['listes'] = explode(',', $listes);
		}

		$unsubscribe = charger_fonction('unsubscribe', 'newsletter');
		$unsubscribe($email, $options);

		$subscriber = charger_fonction('subscriber', 'newsletter');
		$infos = $subscriber($email);

		$output->writeln(var_export($infos, true));

		return self::SUCCESS;
	}
}
