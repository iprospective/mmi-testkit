<?php
namespace Mmi\TestKit\Cli;

use Mmi\TestKit\Diagnostics;
use Mmi\TestKit\HistoryStore;
use Mmi\TestKit\Manifest;
use Mmi\TestKit\Runner;

class Application
{
	public function run($argv)
	{
		$options = getopt('', array('manifest:', 'dolibarr-root:', 'subset:', 'report:', 'report-dir:', 'history', 'diagnose', 'limit:', 'keep', 'verbose', 'list', 'help'));

		if (isset($options['help']) || !isset($options['manifest'])) {
			$this->usage();
			return isset($options['help']) ? 0 : 1;
		}

		try {
			$manifest = Manifest::load($options['manifest']);
		} catch (\Throwable $e) {
			fwrite(STDERR, $e->getMessage()."\n");
			return 1;
		}

		if (isset($options['list'])) {
			foreach ($manifest->suites() as $suite) {
				echo (isset($suite['id']) ? $suite['id'] : '?')."\t".(isset($suite['class']) ? $suite['class'] : '')."\n";
			}
			return 0;
		}

		$reportDir = isset($options['report-dir']) ? $options['report-dir'] : dirname(__DIR__, 2).'/reports';
		$store = new HistoryStore($reportDir);

		if (isset($options['history'])) {
			$this->printHistory($store, $manifest, isset($options['limit']) ? (int) $options['limit'] : 20);
			return 0;
		}

		if (isset($options['diagnose'])) {
			$this->printDiagnose($store, $manifest);
			return 0;
		}

		if (!isset($options['dolibarr-root'])) {
			fwrite(STDERR, "--dolibarr-root requis\n");
			return 1;
		}

		$subset = array();
		if (isset($options['subset'])) {
			$subset = array_values(array_filter(array_map('trim', explode(',', $options['subset']))));
		}

		$runner = new Runner();
		try {
			$result = $runner->run(
				$manifest,
				$options['dolibarr-root'],
				$subset,
				isset($options['keep']),
				isset($options['verbose'])
			);
		} catch (\Throwable $e) {
			fwrite(STDERR, $e->getMessage()."\n");
			return 1;
		}

		$historyFile = $store->save($result);
		echo "Historique : ".$historyFile."\n";

		if (isset($options['report'])) {
			$dir = dirname($options['report']);
			if (!is_dir($dir)) {
				mkdir($dir, 0777, true);
			}
			file_put_contents($options['report'], json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
			echo "Rapport : ".$options['report']."\n";
		}

		foreach ($result['suites'] as $suite) {
			printf("%-24s %3d contrôle(s), %d échec(s), %d avertissement(s)\n", $suite['suite'], $suite['checks_count'], $suite['failed'], count($suite['warnings']));
		}
		printf("\n%s / %s : %d contrôle(s), %d échec(s), %d avertissement(s)\n", $result['env'], $result['code']['commit'] ? $result['code']['commit'] : 'sans commit', $result['summary']['checks'], $result['summary']['failed'], $result['summary']['warnings']);

		foreach ($result['suites'] as $suite) {
			foreach ($suite['warnings'] as $warning) {
				printf("  ! [%s] %s : %s\n", $suite['suite'], $warning['label'], $warning['message']);
			}
			foreach ($suite['failures'] as $failure) {
				printf("  - [%s] %s : got=%s want=%s\n", $suite['suite'], $failure['label'], var_export($failure['got'], true), var_export($failure['want'], true));
			}
		}

		return $result['summary']['ok'] ? 0 : 1;
	}

	private function printHistory(HistoryStore $store, Manifest $manifest, $limit)
	{
		$runs = $store->runs($manifest->envName(), $limit);
		if (empty($runs)) {
			echo "Aucun run enregistré pour ".$manifest->envName()."\n";
			return;
		}
		printf("%-20s %-8s %-18s %6s %6s %6s\n", 'date', 'commit', 'branche', 'checks', 'échecs', 'warn');
		foreach ($runs as $run) {
			printf("%-20s %-8s %-18s %6d %6d %6d\n",
				substr($run['date'], 0, 19),
				substr($run['code']['commit'], 0, 8),
				$run['code']['branch'],
				$run['summary']['checks'],
				$run['summary']['failed'],
				$run['summary']['warnings']
			);
		}
	}

	private function printDiagnose(HistoryStore $store, Manifest $manifest)
	{
		$env = $manifest->envName();
		$diag = $store->diagnose($env);
		if (empty($diag['runs'])) {
			echo "Aucun run enregistré pour ".$env."\n";
			return;
		}

		$latest = $diag['latest'];
		printf("Env %s — %d run(s)\n", $env, $diag['runs']);
		printf("Dernier run : %s commit %s (%s) — %d contrôles, %d échec(s), %d avertissement(s)\n",
			$latest['date'], substr($latest['commit'], 0, 8), $latest['branch'],
			$latest['checks'], $latest['failed'], $latest['warnings']);

		$hints = new Diagnostics();
		if (empty($diag['failures'])) {
			echo "Aucun échec sur le dernier run.\n";
		} else {
			echo "\nÉchecs :\n";
			foreach ($diag['failures'] as $failure) {
				printf("  [%s] %s : got=%s want=%s\n", $failure['suite'], $failure['label'], var_export($failure['got'], true), var_export($failure['want'], true));
				printf("    premier échec : %s (%s)\n", substr($failure['first_failure_commit'], 0, 8), $failure['first_failure_date']);
				printf("    dernier succès : %s (%s)\n", $failure['last_pass_commit'] ? substr($failure['last_pass_commit'], 0, 8) : 'aucun', $failure['last_pass_date'] ?: '—');
				$hint = $hints->hint($failure['suite'], $failure['label']);
				if ($hint !== '') {
					echo "    diagnostic : ".$hint."\n";
				}
			}
		}

		if (!empty($diag['warnings'])) {
			echo "\nAvertissements :\n";
			foreach ($diag['warnings'] as $warning) {
				printf("  [%s] %s : %s\n", $warning['suite'], $warning['label'], $warning['message']);
				$hint = $hints->hint($warning['suite'], $warning['label']);
				if ($hint !== '') {
					echo "    diagnostic : ".$hint."\n";
				}
			}
		}
	}

	private function usage()
	{
		echo "Usage: mmi-test --manifest=FILE --dolibarr-root=DIR [options]\n";
		echo "       mmi-test --manifest=FILE --history [--limit=N]\n";
		echo "       mmi-test --manifest=FILE --diagnose\n";
		echo "  --manifest=FILE        manifeste YAML\n";
		echo "  --dolibarr-root=DIR    répertoire htdocs de l’instance\n";
		echo "  --subset=a,b           ne lancer que certaines suites\n";
		echo "  --report=FILE          rapport JSON supplémentaire\n";
		echo "  --report-dir=DIR       répertoire d’historique (défaut reports/)\n";
		echo "  --history              liste les runs enregistrés\n";
		echo "  --diagnose             diagnostic du dernier run\n";
		echo "  --limit=N              nombre de runs affichés\n";
		echo "  --keep                 commit au lieu de rollback (debug)\n";
		echo "  --verbose              détail des contrôles\n";
		echo "  --list                 liste les suites\n";
	}
}
