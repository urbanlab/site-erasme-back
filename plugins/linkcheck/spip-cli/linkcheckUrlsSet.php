<?php

use Spip\Cli\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressHelper;


class linkcheckUrlsSet extends Command {
	protected function configure() {
		$this
			->setName('linkcheck:urls:set')
			->setDescription('Mettre un code forfaitaire par lot d\'URLs connues')
			->addOption(
				'id_linkcheck',
				null,
				InputOption::VALUE_OPTIONAL,
				'Pour affecter un ou des IDs en particulier',
				null
			)
			->addOption(
				'etat',
				null,
				InputOption::VALUE_OPTIONAL,
				'Pour filtrer sur un un état en particulier',
				null
			)
			->addOption(
				'url-like',
				null,
				InputOption::VALUE_OPTIONAL,
				'Pour filtrer en LIKE sur un pattern d\'url en particulier',
				null
			)
			->addOption(
				'url-regpexp',
				null,
				InputOption::VALUE_OPTIONAL,
				'Pour filtrer en LIKE sur un pattern d\'url en particulier',
				null
			)
			->addOption(
				'status',
				null,
				InputOption::VALUE_OPTIONAL,
				'Code de status http à mettre sur les URLs concernées',
				null
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		include_spip('inc/linkcheck');

		if (!$status = $input->getOption('status')) {
			$this->io->error("Indiquez un status HTTP à mettre sur les URLs concernées");
			return self::FAILURE;
		}


		$where = [];
		if ($id_linkchecks = $input->getOption('id_linkcheck')) {
			$id_linkchecks = explode(',', $id_linkchecks);
			$id_linkchecks = array_map('intval', $id_linkchecks);
			$id_linkchecks = array_filter($id_linkchecks);
			$id_linkchecks = array_unique($id_linkchecks);
			if (empty($id_linkchecks)) {
				$this->io->error("Indiquez un ou plusieurs ID non nuls dans --id_linkcheck");
				return self::FAILURE;
			}
			$where[] = sql_in('id_linkcheck', $id_linkchecks);
		}

		if (($etat = $input->getOption('etat')) !== null) {
			$where[] = "etat=" . sql_quote($etat ?: '');
		}

		if (($url = $input->getOption('url-like'))) {
			$where[] = "url LIKE " . sql_quote($url);
		}
		if (($url = $input->getOption('url-regpexp'))) {
			$where[] = "url REGEXP " . sql_quote($url);
		}

		$nb = sql_countsel("spip_linkchecks", $where);
		$this->io->care(json_encode($where));

		if (!$nb) {
			$this->io->care("Aucune URL ne correspond");
			return self::FAILURE;
		}

		$this->io->care("$nb URLS correspondent");
		if ($confirm = $this->io->confirm("Mettre le code '$status' sur les $nb URLs ?")) {
			$set = [
				'code' => $status,
				'etat' => linkcheck_etats_liens($status),
				'maj' => date('Y-m-d H:i:s'),
			];
			$this->io->care(json_encode($set));
			if (sql_updateq('spip_linkchecks', $set, $where)) {
				$this->io->check('URLs modifiées');
				return self::SUCCESS;
			}
		}

		return self::FAILURE;
	}
}
