<?php
namespace Mmi\TestKit\Workflows;

use Mmi\TestKit\TestCase;

class Security
{
	private $ctx;
	private $t;
	private $db;
	private $user;

	public function __construct($ctx, TestCase $test)
	{
		$this->ctx = $ctx;
		$this->t = $test;
		$this->db = $ctx['db'];
		$this->user = $ctx['user'];
	}

	public function run()
	{
		require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

		$t = $this->t;
		$t->check('environnement marqué test', getDolGlobalInt('MMI_TEST_ENV'), 1);
		$t->ok('admin de test', !empty($this->user->admin));

		$t->etape('1. Droits d’un utilisateur non admin');
		$login = 'zzsec'.substr((string) dol_now(), -6).mt_rand(10, 99);
		$u = new \User($this->db);
		$u->login = $login;
		$u->lastname = 'ZZ Security';
		$u->firstname = 'Test';
		$u->admin = 0;
		$u->statut = 1;
		$u->pass = 'Test3579!2026';
		$u->email = $login.'@test.local';
		$id = $u->create($this->user);
		$t->ok('utilisateur non admin créé', $id > 0);
		if ($id <= 0) {
			$t->fail('sécurité utilisateur', $u->error.' '.json_encode($u->errors));
			return;
		}

		if (method_exists($u, 'loadRights')) {
			$u->loadRights('', 1);
		} else {
			$u->getrights('', 1);
		}
		$t->ok('aucun droit facture/lire par défaut', !$u->hasRight('facture', 'lire'));
		$t->ok('aucun droit facture/créer par défaut', !$u->hasRight('facture', 'creer'));

		$resql = $this->db->query('SELECT id FROM '.MAIN_DB_PREFIX.'rights_def WHERE module = \'facture\' AND perms = \'lire\' AND entity = '.((int) $this->ctx['conf']->entity).' LIMIT 1');
		$right = $resql ? $this->db->fetch_object($resql) : null;
		$t->ok('droit facture/lire défini', $right !== null);
		if ($right) {
			$this->db->query('INSERT INTO '.MAIN_DB_PREFIX.'user_rights (entity, fk_user, fk_id) VALUES ('.((int) $this->ctx['conf']->entity).', '.((int) $u->id).', '.((int) $right->id).')');
			if (method_exists($u, 'loadRights')) {
				$u->loadRights('', 1);
			} else {
				$u->getrights('', 1);
			}
			$t->ok('droit facture/lire accordé', $u->hasRight('facture', 'lire'));
			$t->ok('droit facture/créer toujours refusé', !$u->hasRight('facture', 'creer'));
		}

		$t->etape('2. Échappement HTML');
		$escaped = dol_escape_htmltag('<script>alert(1)</script>');
		$t->ok('balise script échappée', strpos($escaped, '<script>') === false);

		$t->etape('3. Jeton CSRF');
		$token = newToken();
		if (!is_string($token) || strlen($token) < 16) {
			$t->warn('csrf', 'jeton CSRF non testable en CLI (session absente)');
		} else {
			$t->ok('jeton CSRF non vide', true);
			$t->check('currentToken cohérent', currentToken(), $token);
		}
	}
}
