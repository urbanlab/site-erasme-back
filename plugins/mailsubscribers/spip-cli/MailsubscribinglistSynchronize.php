<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressHelper;

class MailsubscribinglistSynchronize extends Command {
	protected function configure() {
		$this
			->setName('mailsubscribinglist:syncronize')
			->setDescription('Synchroniser une ou des listes automatiques')
			->addOption(
				'listes',
				null,
				InputOption::VALUE_OPTIONAL,
				'Listes à synchroniser, séparées par des virgules',
				null
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		include_spip('inc/filtres');


		$listes = $input->getOption('listes');
		if (!$listes) {
			$output->writeln('<error>Indiquez la ou les listes à synchroniser</error>');
			return self::FAILURE;
		}
		$listes = explode(',', $listes);

		include_spip('inc/mailsubscribers');

		foreach ($listes as $liste) {
			$this->io->section("Synchronisation liste $liste");
			if ($f = mailsubscribers_trouver_fonction_synchro($liste)) {
				$abonnes = $f();
				if (
					is_array($abonnes)
					and (!count($abonnes) or ($r = reset($abonnes) and isset($r['email'])))
				) {
					$n = count($abonnes);
					$this->io->write("Synchronise liste '$liste' avec $n abonnes (fonction $f)");
					if (!mailsubscribers_synchronise_liste($liste, $abonnes)) {
						$this->io->care('synchronisation incomplete');
					}
					else {
						$this->io->check('liste synchronisee');
					}
				} else {
					$this->io->fail("Synchronise liste $liste : abonnes mal formes en retour de la fonction $f");
				}
			}
			else {
				$this->io->fail('aucune fonction de synchronisation pour cette liste');
			}
		}

		$this->io->success('Synchronisation terminée');
		return self::SUCCESS;
	}
}
