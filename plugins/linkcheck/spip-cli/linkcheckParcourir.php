<?php

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressHelper;


class linkcheckParcourir extends Command {
	protected function configure() {
		$this
			->setName('linkcheck:parcourir')
			->setDescription('Parcours toute la base pour rechercher les liens')
			->addOption(
				'purge',
				null,
				InputOption::VALUE_NONE,
				'Purge la liste des liens connus avant de parcourir tout',
				null
			)
			->addOption(
				'id_branche',
				null,
				InputOption::VALUE_OPTIONAL,
				'Indiquez un id_rubrique pour limiter le parcours à une branche du site',
				null
			)
		;
	}

	public static function statusParcours() {
		$status = [];
		include_spip('linkcheck_fonctions');
		include_spip('inc/filtres');
		$stats = linkcheck_chiffre();
		$status[] = ($stats['nb_lien'] ? singulier_ou_pluriel($stats['nb_lien'], 'linkcheck:info_1_linkcheck', 'linkcheck:info_nb_linkchecks') : _T('linkcheck:info_aucun_lien'));
		$status[] = ($stats['parcours_progress'] >= 0.01 ? _T('linkcheck:info_parcours_en_cours', ['progress' => $stats['parcours_progress']]) : _T('linkcheck:info_parcours_todo'));
		$status[] = ($stats['parcours_objets_total'] ? singulier_ou_pluriel($stats['parcours_objets_total'], 'linkcheck:info_1_objet_a_parcourir', 'linkcheck:info_nb_objets_a_parcourir') : _T('linkcheck:info_aucun_objet_a_parcourir'));

		return $status;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		include_spip('inc/linkcheck');
		include_fichiers_fonctions();

		$this->io->title("Parcourir le site pour lister les URLs");

		$purge = $input->getOption('purge');
		if ($purge) {
			linkcheck_purger();
			$this->io->check("Purge de la base");
		} else {
			$status = linkcheckParcourir::statusParcours();
			foreach ($status as $s) {
				$this->io->care($s);
			}
		}

		$id_branche = $input->getOption('id_branche');
		if ($id_branche = intval($id_branche)) {
			$this->io->care("Limiter à la branche id_rubrique=" . $id_branche);
		}

		$tables_a_traiter = linkcheck_tables_a_traiter();
		$liste_tables = array_keys($tables_a_traiter);
		$this->io->care(count($liste_tables)." tables à traiter : " . implode(', ', $liste_tables));

		$stats = linkcheck_chiffre();
		$this->io->progressStart($stats['parcours_objets_total']);
		sleep(1);
		$this->io->progressAdvance($stats['parcours_objets_done']);

		do {
			$timeout = time() + 5;
			$fini = linkcheck_parcourir($id_branche, $timeout);
			$step = $stats['parcours_objets_done'];
			$stats = linkcheck_chiffre();
			$step = $stats['parcours_objets_done'] - $step;
			$this->io->progressAdvance($step);
			//$this->io->care("Avancement " .$stats['parcours_progress'] . '% (' . $stats['nb_lien'] . ' liens)');
		} while (!$fini);

		$this->io->progressFinish();
		return self::SUCCESS;

	}
}
