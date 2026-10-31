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

		$test = new TestCase($manifest->site(), $verbose);
		$results = array();

		foreach ($manifest->suites() as $suite) {
			$id = isset($suite['id']) ? (string) $suite['id'] : '';
			if ($id === '') {
				$test->fail('manifest', 'suite sans id');
				continue;
			}
			if (!empty($subset) && !in_array($id, $subset, true)) {
				continue;
			}
			$class = isset($suite['class']) ? (string) $suite['class'] : '';
			if ($class === '' || !class_exists($class)) {
				$test->fail($id, 'classe de workflow introuvable : '.$class);
				continue;
			}
			$test->etape($id);
			try {
				/** @var object $workflow */
				$workflow = new $class($ctx, $test);
				$workflow->run();
			} catch (\Throwable $e) {
				$test->fail($id, get_class($e).' : '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
			}
		}

		if ($keep) {
			$db->commit();
		} else {
			$db->rollback();
		}

		$results = $test->summary();
		$results['date'] = date('c');
		$results['php'] = PHP_VERSION;
		$results['dolibarr_root'] = $dolibarrRoot;
		$results['dolibarr_version'] = isset($ctx['conf']->global->MAIN_VERSION_LAST_UPGRADE) ? $ctx['conf']->global->MAIN_VERSION_LAST_UPGRADE : '';
		$results['kept'] = (bool) $keep;

		return $results;
	}
}
