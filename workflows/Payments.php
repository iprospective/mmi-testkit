<?php
namespace Mmi\TestKit\Workflows;

use Mmi\TestKit\TestCase;

class Payments
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
		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

		$t = $this->t;
		$fixtures = new \Mmi\TestKit\Fixtures\FixtureBuilder($this->db, $this->user, $this->ctx['mysoc']);
		$soc = $fixtures->createCustomer('ZZ Payments');
		$t->ok('tiers de test créé', $soc !== null);
		if (!$soc) {
			return;
		}
		$product = $fixtures->ensureProduct(20.0, 'ZZPAY20', 'Produit paiement', 100.0);
		$t->ok('produit de test créé', $product !== null);
		if (!$product) {
			return;
		}

		$fac = new \Facture($this->db);
		$fac->socid = $soc->id;
		$fac->date = dol_now();
		$fac->type = 0;
		$id = $fac->create($this->user, 1);
		$t->ok('facture créée', $id > 0);
		if ($id <= 0) {
			return;
		}
		$fac->addline('Ligne paiement', 100, 1, 20, 0, 0, $product->id);
		$fac->fetch($id);
		$t->ok('facture validée', $fac->validate($this->user) > 0);
		$fac->fetch($id);
		$t->checkClose('total TTC de la facture', $fac->total_ttc, 120, 0.01);

		$resql = $this->db->query('SELECT id FROM '.MAIN_DB_PREFIX.'c_paiement WHERE active = 1 ORDER BY id LIMIT 1');
		$mode = $resql ? (int) $this->db->fetch_object($resql)->id : 0;
		$t->ok('moyen de paiement disponible', $mode > 0);

		$t->etape('1. Paiement partiel');
		$pai = new \Paiement($this->db);
		$pai->datepaye = dol_now();
		$pai->amounts = array($fac->id => 60.0);
		$pai->paiementid = $mode;
		$idp = $pai->create($this->user, 0);
		$t->ok('paiement partiel enregistré', $idp > 0);
		$fac->fetch($id);
		$t->checkClose('somme payée après paiement partiel', $fac->getSommePaiement(), 60, 0.01);
		$t->check('facture non payée après paiement partiel', (int) $fac->paye, 0);

		$t->etape('2. Solde');
		$pai2 = new \Paiement($this->db);
		$pai2->datepaye = dol_now();
		$pai2->amounts = array($fac->id => 60.0);
		$pai2->paiementid = $mode;
		$idp2 = $pai2->create($this->user, 1);
		$t->ok('paiement du solde enregistré', $idp2 > 0);
		$fac->fetch($id);
		$t->checkClose('somme payée totale', $fac->getSommePaiement(), 120, 0.01);
		$t->check('facture payée', (int) $fac->paye, 1);

		$resql = $this->db->query('SELECT COUNT(*) as nb FROM '.MAIN_DB_PREFIX.'paiement_facture WHERE fk_facture = '.((int) $fac->id));
		$obj = $resql ? $this->db->fetch_object($resql) : null;
		$t->ok('deux affectations de paiement', $obj && (int) $obj->nb === 2);
	}
}
