<?php

namespace Spip\Cli\Command;

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressHelper;

class MailsubscribinglistUpdateSegments extends Command {
	protected function configure() {
		$this
			->setName('mailsubscribinglist:update:segments')
			->setDescription('Mettre a jour tous les segments d\'une ou plusieurs listes de souscription')
			->addOption(
				'listes',
				null,
				InputOption::VALUE_OPTIONAL,
				'Listes à mettre à jour, séparées par des virgules. Par défaut, toutes',
				null
			)
			->addOption(
				'segments',
				null,
				InputOption::VALUE_OPTIONAL,
				'Segments à mettre à jour, séparés par des virgules. Par défaut, tous',
				null
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {

		include_spip('inc/filtres');
		include_spip('inc/mailsubscribinglists');

		$in_listes = '';
		$listes = $input->getOption('listes');
		if (!is_null($listes)) {
			$listes = explode(',', $listes);
			$in_listes = sql_in('identifiant', $listes);
		}

		$update_segments = $input->getOption('segments');
		if (!is_null($update_segments)) {
			$update_segments = explode(',', $update_segments);
			$update_segments = array_map('trim', $update_segments);
		}

		$mailsubscribinglists = sql_allfetsel('*', 'spip_mailsubscribinglists', $in_listes);


		foreach ($mailsubscribinglists as $mailsubscribinglist) {
			$id_mailsubscribinglist = $mailsubscribinglist['id_mailsubscribinglist'];
			$this->io->care("#$id_mailsubscribinglist " . $mailsubscribinglist['titre']);

			$id_segments = [];
			$segments = unserialize($mailsubscribinglist['segments']);
			if ($segments and $update_segments) {
				foreach ($update_segments as $update_segment) {
					if (is_numeric($update_segment)) {
						if (!empty($segments[$update_segment])) {
							$id_segments[] = $update_segment;
						}
					}
					else {
						foreach ($segments as $id => $segment) {
							if (stripos($segment['titre'], $update_segment) !== false) {
								$id_segments[] = $id;
							}
						}
					}
				}
			}
			if (count($id_segments)) {
				$force = false;
				$GLOBALS['meta']['mailsubscriptions_update_segments'][$id_mailsubscribinglist] = $id_segments;
				$output->writeln('Mise a jour des segments #' . implode(', #', $id_segments));
			}
			else {
				$force = true;
				$output->writeln('Mise a jour de tous les segments (' . count($segments) . ')');
			}

			$res = sql_select('*', 'spip_mailsubscriptions', "id_segment=0 AND statut='valide' AND id_mailsubscribinglist=" . intval($id_mailsubscribinglist));
			$nb = sql_count($res);
			$output->writeln("$nb Mailsubscribers");
			$i = 0;
			while ($row = sql_fetch($res)) {
				mailsubscribers_actualise_mailsubscribinglist_segments($row['id_mailsubscriber'], $row['id_mailsubscribinglist'], $force);
				if ($force and $row['actualise_segments']) {
					sql_updateq('spip_mailsubscriptions', ['actualise_segments' => 0], 'id_mailsubscriber=' . intval($row['id_mailsubscriber']) . ' AND ' . 'id_mailsubscribinglist=' . intval($row['id_mailsubscribinglist']));
				}
				$i++;
				if ($i % 200 == 0) {
					$output->writeln("$i/$nb mailsubscribers mis a jour");
				}
			}
			$this->io->check("$i/$nb mailsubscribers mis a jour");
		}

		$this->io->check('Mise a jour terminée');
		return self::SUCCESS;
	}
}
