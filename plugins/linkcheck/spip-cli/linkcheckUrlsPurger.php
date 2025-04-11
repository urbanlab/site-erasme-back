<?php

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressHelper;


class linkcheckUrlsPurger extends Command {
	protected function configure() {
		$this
			->setName('linkcheck:urls:purger')
			->setDescription('Purger la base des URLs répertoriées')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		include_spip('inc/linkcheck');

		$this->io->title("Purger la base des URLs");
		if (sql_countsel('spip_linkchecks')) {
			linkcheck_purger();
			$this->io->check("Purge de la base");
		} else {
			$this->io->care("Base déjà vide");
		}

		$status = linkcheckParcourir::statusParcours();
		foreach ($status as $s) {
			$this->io->care($s);
		}

		return self::SUCCESS;

	}
}
