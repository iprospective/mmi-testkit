<?php
namespace Mmi\TestKit\Workflows;

use Mmi\TestKit\TestCase;

/**
 * S6 — contrat de synchronisation PrestaShop.
 *
 * La synchro réelle nécessite un PrestaShop et un secret partagé. Cette suite
 * vérifie le contrat local (module, hooks, triggers, configuration) et signale
 * explicitement ce qui n'est pas exerçable sur l'environnement de test.
 */
class Sync
{
	private $t;
	private $db;

	public function __construct($ctx, TestCase $test)
	{
		$this->t = $test;
		$this->db = $ctx['db'];
	}

	public function run()
	{
		$t = $this->t;
		$t->check('module mmiprestasync actif', isModEnabled('mmiprestasync'), true);
		if (!isModEnabled('mmiprestasync')) {
			$t->warn('sync', 'module mmiprestasync non actif : synchro non exercée');
			return;
		}

		$t->ok('constante MAIN_MODULE_MMIPRESTASYNC', getDolGlobalInt('MAIN_MODULE_MMIPRESTASYNC') === 1);
		$t->ok('hooks enregistrés', getDolGlobalString('MAIN_MODULE_MMIPRESTASYNC_HOOKS') !== '');
		$t->ok('triggers enregistrés', getDolGlobalInt('MAIN_MODULE_MMIPRESTASYNC_TRIGGERS') === 1);

		$resql = $this->db->query("SHOW TABLES LIKE '".MAIN_DB_PREFIX."_sync'");
		$hasQueue = $resql && $this->db->num_rows($resql) > 0;
		if ($hasQueue) {
			$resql = $this->db->query('SELECT COUNT(*) as nb FROM '.MAIN_DB_PREFIX.'_sync');
			$obj = $resql ? $this->db->fetch_object($resql) : null;
			$t->ok('table de file de synchro interrogeable', $obj !== null);
		} else {
			$t->warn('sync', 'table de file _sync absente : synchro non exercée');
		}

		if (getDolGlobalString('MMIPRESTASYNC_WS_SYNC_PASS') === '') {
			$t->warn('sync', 'secret WS_SYNC_PASS non configuré : appel PrestaShop non testé (normal en recette)');
		}
	}
}
