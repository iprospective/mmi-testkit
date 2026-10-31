<?php
namespace Mmi\TestKit\Cli;

use Mmi\TestKit\Manifest;
use Mmi\TestKit\Runner;

class Application
{
	public function run($argv)
	{
		$options = getopt('', array('manifest:', 'dolibarr-root:', 'subset:', 'report:', 'keep', 'verbose', 'list', 'help'));

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

		if (isset($options['report'])) {
			$dir = dirname($options['report']);
			if (!is_dir($dir)) {
				mkdir($dir, 0777, true);
			}
			file_put_contents($options['report'], json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
			echo "Rapport : ".$options['report']."\n";
		}

		printf("\n%s : %d contrôle(s), %d échec(s)\n", $result['suite'], $result['checks'], $result['failed']);
		foreach ($result['warnings'] as $warning) {
			printf("  ! %s : %s\n", $warning['label'], $warning['message']);
		}
		foreach ($result['failures'] as $failure) {
			printf("  - %s : got=%s want=%s\n", $failure['label'], var_export($failure['got'], true), var_export($failure['want'], true));
		}

		return $result['ok'] ? 0 : 1;
	}

	private function usage()
	{
		echo "Usage: mmi-test --manifest=FILE --dolibarr-root=DIR [options]\n";
		echo "  --manifest=FILE        manifeste YAML\n";
		echo "  --dolibarr-root=DIR    répertoire htdocs de l’instance\n";
		echo "  --subset=a,b           ne lancer que certaines suites\n";
		echo "  --report=FILE          rapport JSON\n";
		echo "  --keep                 commit au lieu de rollback (debug)\n";
		echo "  --verbose              détail des contrôles\n";
		echo "  --list                 liste les suites\n";
	}
}
