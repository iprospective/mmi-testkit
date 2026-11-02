<?php
namespace Mmi\TestKit;

class Runner
{
	/**
	 * Exécute les suites d’un manifeste dans une transaction annulée.
	 *
	 * @param Manifest $manifest
	 * @param string   $dolibarrRoot
	 * @param string[] $subset
	 * @param bool     $keep Commit au lieu de rollback (debug uniquement)
	 * @param bool     $verbose
	 * @return array
	 */
	public function run(Manifest $manifest, $dolibarrRoot, $subset = array(), $keep = false, $verbose = false)
	{
		$ctx = Bootstrap::boot($dolibarrRoot);
		$db = $ctx['db'];

		$db->begin();

		$suiteResults = array();
		$totalChecks = 0;
		$totalPassed = 0;
		$totalFailed = 0;
		$totalWarnings = 0;

		foreach ($manifest->suites() as $suite) {
			$id = isset($suite['id']) ? (string) $suite['id'] : '';
			if ($id === '') {
				continue;
			}
			if (!empty($subset) && !in_array($id, $subset, true)) {
				continue;
			}

			$test = new TestCase($id, $verbose);
			$class = isset($suite['class']) ? (string) $suite['class'] : '';
			if ($class === '' || !class_exists($class)) {
				$test->fail($id, 'classe de workflow introuvable : '.$class);
			} else {
				$test->etape($id);
				try {
					/** @var object $workflow */
					$workflow = new $class($ctx, $test);
					$workflow->run();
				} catch (\Throwable $e) {
					$test->fail($id, get_class($e).' : '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
				}
			}

			$summary = $test->summary();
			$suiteResults[] = $summary;
			$totalChecks += $summary['checks_count'];
			$totalPassed += $summary['passed'];
			$totalFailed += $summary['failed'];
			$totalWarnings += count($summary['warnings']);
		}

		if ($keep) {
			$db->commit();
		} else {
			$db->rollback();
		}

		$env = Environment::load($dolibarrRoot);
		$commit = isset($env['commit']) ? (string) $env['commit'] : '';
		$runId = date('Ymd-His').'-'.($commit !== '' ? substr($commit, 0, 7) : 'nocode').'-'.substr(uniqid('', true), -4);

		return array(
			'schema' => 1,
			'run_id' => $runId,
			'date' => date('c'),
			'env' => $manifest->envName(),
			'site' => $manifest->site(),
			'php' => PHP_VERSION,
			'dolibarr_root' => $dolibarrRoot,
			'dolibarr_version' => isset($ctx['conf']->global->MAIN_VERSION_LAST_UPGRADE) ? $ctx['conf']->global->MAIN_VERSION_LAST_UPGRADE : '',
			'code' => $env,
			'kept' => (bool) $keep,
			'suites' => $suiteResults,
			'summary' => array(
				'suites' => count($suiteResults),
				'checks' => $totalChecks,
				'passed' => $totalPassed,
				'failed' => $totalFailed,
				'warnings' => $totalWarnings,
				'ok' => ($totalFailed === 0),
			),
		);
	}
}
