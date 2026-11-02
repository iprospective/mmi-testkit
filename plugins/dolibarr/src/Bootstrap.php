<?php
namespace Mmi\TestKit;

class Bootstrap
{
	/**
	 * Charge Dolibarr et applique les garde-fous de recette.
	 *
	 * @param string $root Répertoire htdocs de l'instance Dolibarr
	 * @return array{db: object, conf: object, user: object, langs: object, mysoc: object}
	 */
	public static function boot($root)
	{
		$root = rtrim($root, '/');
		if (!is_file($root.'/master.inc.php')) {
			throw new \RuntimeException('master.inc.php introuvable dans '.$root);
		}

		chdir($root);
		require_once $root.'/master.inc.php';

		$GLOBALS['db'] = $db;
		$GLOBALS['conf'] = $conf;
		$GLOBALS['langs'] = $langs;
		$GLOBALS['mysoc'] = $mysoc;
		foreach (array('dolibarr_main_db_host', 'dolibarr_main_db_port', 'dolibarr_main_db_name', 'dolibarr_main_db_user', 'dolibarr_main_db_pass', 'dolibarr_main_data_root', 'dolibarr_main_document_root') as $globalName) {
			if (isset($$globalName)) {
				$GLOBALS[$globalName] = $$globalName;
			}
		}

		/** @var object $db */
		/** @var object $conf */
		/** @var object $user */
		/** @var object $langs */
		/** @var object $mysoc */

		if (empty($user) || empty($user->id)) {
			$user = new \User($db);
			$resql = $db->query('SELECT rowid FROM '.MAIN_DB_PREFIX.'user WHERE admin = 1 AND statut = 1 ORDER BY rowid LIMIT 1');
			if (!$resql) {
				throw new \RuntimeException('Aucun administrateur actif trouvé');
			}
			$obj = $db->fetch_object($resql);
			$user->fetch((int) $obj->rowid);
		}
		if (method_exists($user, 'loadRights')) {
			$user->loadRights('', 1);
		} else {
			$user->getrights('', 1);
		}

		if (empty($mysoc) || !is_object($mysoc) || empty($mysoc->country_id)) {
			require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
			$mysoc = new \Societe($db);
			$mysoc->setMysoc($conf);
		}
		if (empty($mysoc->country_id)) {
			$country = isset($conf->global->MAIN_INFO_SOCIETE_COUNTRY) ? (string) $conf->global->MAIN_INFO_SOCIETE_COUNTRY : '';
			$parts = explode(':', $country);
			$mysoc->country_id = isset($parts[0]) ? (int) $parts[0] : 0;
			$mysoc->country_code = isset($parts[1]) ? $parts[1] : '';
		}

		$GLOBALS['user'] = $user;
		$GLOBALS['mysoc'] = $mysoc;

		if (!getDolGlobalInt('MMI_TEST_ENV')) {
			throw new \RuntimeException('Refus : MMI_TEST_ENV absent. Cet environnement n’est pas marqué comme environnement de test.');
		}

		$conf->global->MAIN_DISABLE_ALL_MAILS = 1;
		$conf->global->MMIPRESTASYNC_SYNC = 0;
		$conf->global->MMIYOUNITED_API_MODE_SANDBOX = 1;
		$langs->setDefaultLang('fr_FR');

		return array(
			'db' => $db,
			'conf' => $conf,
			'user' => $user,
			'langs' => $langs,
			'mysoc' => $mysoc,
			'root' => $root,
		);
	}
}
