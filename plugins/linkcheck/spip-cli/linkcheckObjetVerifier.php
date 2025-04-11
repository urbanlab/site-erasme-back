<?php

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressHelper;


class linkcheckObjetVerifier extends Command {
	protected function configure() {
		$this
			->setName('linkcheck:objet:verifier')
			->setDescription('Recenser et Verifier les liens d\'un objet en particulier')
			->addOption(
				'objet',
				null,
				InputOption::VALUE_REQUIRED,
				'objet',
				null
			)
			->addOption(
				'id_objet',
				null,
				InputOption::VALUE_REQUIRED,
				'id_objet ou liste d\'id_objet séparés par des virgules',
				''
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		include_spip('inc/linkcheck');

		$objet = $input->getOption('objet');
		if (!$objet) {
			$output->writeln("<error>Indiquez un objet a analyser</error>");
			return self::FAILURE;
		}

		$table_sql = table_objet_sql($objet);
		$tables_a_traiter = linkcheck_tables_a_traiter();
		if (empty($tables_a_traiter[$table_sql])) {
			$this->io->fail("Aucun champ déclaré à analyser pour les objets de type '$objet'");
			return self::FAILURE;
		}

		if ($id_objets = $input->getOption('id_objet')) {
			$id_objets = explode(',', $id_objets);
			$id_objets = array_map('intval', $id_objets);
			$id_objets = array_filter($id_objets);
		}
		if (!$id_objets) {
			$output->writeln("<error>Indiquez un ou des id_objet a analyser (séparés par des virgules)</error>");
			return self::FAILURE;
		}

		foreach ($id_objets as $id_objet) {
			$this->io->section("Analyser $objet #$id_objet");
			$id_linkchecks = linkcheck_objet_verifier($objet, $id_objet, false);
			if (empty($id_linkchecks)) {
				$this->io->care("Aucun lien trouvé");
			} else {
				$this->io->check(count($id_linkchecks) . " lien(s) trouvé(s)");
				$this->io->care('Vérification des liens...');

				$res = linkcheck_objet_tester_liens($objet, $id_objet, $id_linkchecks);
				$this->io->atable($res);
			}
		}
		return self::SUCCESS;
	}
}
