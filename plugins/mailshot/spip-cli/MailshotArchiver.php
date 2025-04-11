<?php

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressHelper;

class MailshotArchiver extends Command {
	protected function configure() {
		$this
			->setName('mailshot:archiver')
			->setDescription('Archiver un envoi')
			->addOption(
				'id_mailshot',
				null,
				InputOption::VALUE_REQUIRED,
				'id_mailshot a archiver',
				null
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		include_spip('inc/filtres');


		$id_mailshot = $input->getOption('id_mailshot');
		if (!$id_mailshot) {
			include_spip('mailshot_pipelines');
			$id_mailshot = mailshot_liste_envois_a_archiver();

			if (!$id_mailshot) {
				$output->writeln("<error>Indiquez un id_mailshot (rien a archiver par defaut)</error>");
				exit(1);
			}
		}
		else {
			$id_mailshot = explode(',', $id_mailshot);
		}

		include_spip('inc/mailshot');
		foreach ($id_mailshot as $id) {
			$this->io->section("Archiver Mailshot #$id");
			mailshot_archiver($id);
			$this->io->success("archivé");
		}

	}
}
