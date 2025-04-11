<?php

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressHelper;


class linkcheckUrlsTester extends Command {
	protected function configure() {
		$this
			->setName('linkcheck:urls:tester')
			->setDescription('Tester les URLs de la base')
			->addOption(
				'id_linkcheck',
				null,
				InputOption::VALUE_OPTIONAL,
				'Pour tester un ou des IDs en particulier',
				null
			)
			->addOption(
				'etat',
				null,
				InputOption::VALUE_OPTIONAL,
				'Pour tester les URLs d\'un état en particulier' ,
				null
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		include_spip('inc/linkcheck');

		if ($id_linkchecks = $input->getOption('id_linkcheck')) {
			$id_linkchecks = explode(',', $id_linkchecks);
			$id_linkchecks = array_map('intval', $id_linkchecks);
			$id_linkchecks = array_filter($id_linkchecks);
			$id_linkchecks = array_unique($id_linkchecks);
			if (empty($id_linkchecks)) {
				$this->io->error("Indiquez un ou plusieurs ID non nuls dans --id_linkcheck");
				return self::FAILURE;
			}

			$this->io->title("Tester des URLs");
			$linkchecks = sql_allfetsel('*', 'spip_linkchecks', sql_in('id_linkcheck', $id_linkchecks));
			$this->io->care(count($linkchecks) ." liens");
			foreach ($linkchecks as $linkcheck) {
				$this->io->section("#" . $linkcheck['id_linkcheck']. " " . $linkcheck['url']);
				$set = linkcheck_tester_un_linkcheck($linkcheck, true);
				$this->io->atable([$set]);
			}
			return self::SUCCESS;
		}

		$etat = $input->getOption('etat');
		if (!empty($etat)) {
			$this->io->title("Tester les URLs en état '$etat'");
			$where = "etat=" . sql_quote($etat);
			$order = 'maj DESC';
		} else {
			$this->io->title("Tester les URLs en état inconnu");
			$where = "etat=''";
			$order = 'id_linkcheck';
		}

		$nb = sql_countsel('spip_linkchecks', $where);
		$this->io->care("$nb URLs à tester");
		$now_start = date('Y-m-d H:i:s');
		$this->io->progressStart($nb);
		do {
			// par défaut on veut tester les linkchecks en etat inconnu
			$linkchecks = sql_allfetsel('*', 'spip_linkchecks', "$where AND maj < " . sql_quote($now_start), '', $order, "0,100");
			foreach ($linkchecks as $linkcheck) {
				//$this->io->care('#' . $linkcheck['id_linkcheck'] . ' ' . $linkcheck['url']);
				linkcheck_tester_un_linkcheck($linkcheck);
				$this->io->progressAdvance();
			}
		} while (!empty($linkchecks));

		$this->io->progressFinish();
		return self::SUCCESS;
	}
}
