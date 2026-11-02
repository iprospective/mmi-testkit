<?php
namespace Mmi\TestKit\Workflows;

use Mmi\TestKit\TestCase;

class Shipping
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
		require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
		require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

		$t = $this->t;
		$fixtures = new \Mmi\TestKit\Fixtures\FixtureBuilder($this->db, $this->user, $this->ctx['mysoc']);
		$soc = $fixtures->createCustomer('ZZ Shipping');
		$t->ok('tiers de test créé', $soc !== null);
		if (!$soc) {
			return;
		}
		$product = $fixtures->ensureProduct(20.0, 'ZZSHIP20', 'Produit expédition', 80.0);
		$t->ok('produit de test créé', $product !== null);
		if (!$product) {
			return;
		}
		$warehouse = $this->ensureWarehouse();
		$t->ok('entrepôt de test disponible', $warehouse !== null);
		if (!$warehouse) {
			return;
		}
		$product->correct_stock($this->user, $warehouse->id, 10, 0, 'Stock expédition');
		$before = $this->stock($product->id, $warehouse->id);

		$t->etape('1. Commande');
		$cmd = new \Commande($this->db);
		$cmd->socid = $soc->id;
		$cmd->date = dol_now();
		$id = $cmd->create($this->user);
		$t->ok('commande créée', $id > 0);
		if ($id <= 0) {
			return;
		}
		$cmd->addline('Ligne expédition', 80, 2, 20, 0, 0, $product->id);
		$cmd->fetch($id);
		$t->ok('commande validée', $cmd->valid($this->user) > 0);
		$cmd->fetch($id);

		$t->etape('2. Expédition');
		$exp = new \Expedition($this->db);
		$exp->socid = $soc->id;
		$exp->origin = 'commande';
		$exp->origin_id = $cmd->id;
		$rline = $exp->addline($warehouse->id, $cmd->lines[0]->id, 2);
		$t->ok('ligne d’expédition ajoutée', $rline > 0);
		if ($rline <= 0) {
			$t->fail('ligne expédition', $exp->error.' '.json_encode($exp->errors));
			return;
		}
		$ide = $exp->create($this->user);
		$t->ok('expédition créée', $ide > 0);
		if ($ide <= 0) {
			$t->fail('expédition', $exp->error);
			return;
		}
		$rvalid = $exp->valid($this->user);
		$t->ok('expédition validée', $rvalid > 0);
		if ($rvalid <= 0) {
			$t->fail('validation expédition', $exp->error.' '.json_encode($exp->errors));
			return;
		}
		$exp->fetch($ide);
		$t->ok('statut expédition validé', (int) $exp->statut > 0);
		$resql = $this->db->query('SELECT COUNT(*) as nb FROM '.MAIN_DB_PREFIX.'expeditiondet WHERE fk_expedition = '.((int) $ide));
		$obj = $resql ? $this->db->fetch_object($resql) : null;
		$t->ok('ligne d’expédition en base', $obj && (int) $obj->nb >= 1);

		$after = $this->stock($product->id, $warehouse->id);
		$t->checkClose('stock décrémenté de la quantité expédiée', $after, $before - 2, 0.001);
	}

	private function stock($productId, $warehouseId)
	{
		$resql = $this->db->query('SELECT reel FROM '.MAIN_DB_PREFIX.'product_stock WHERE fk_product = '.((int) $productId).' AND fk_entrepot = '.((int) $warehouseId));
		$obj = $resql ? $this->db->fetch_object($resql) : null;
		return $obj ? (float) $obj->reel : 0.0;
	}

	private function ensureWarehouse()
	{
		$warehouse = new \Entrepot($this->db);
		if ($warehouse->fetch(0, 'ZZ Entrepot test') > 0) {
			if ((int) $warehouse->statut !== 1) {
				$warehouse->statut = 1;
				$warehouse->update($warehouse->id, $this->user);
			}
			return $warehouse;
		}
		$warehouse->label = 'ZZ Entrepot test';
		$warehouse->lieu = 'Test';
		$warehouse->statut = 1;
		$id = $warehouse->create($this->user);
		if ($id <= 0) {
			$this->t->fail('entrepôt', $warehouse->error);
			return null;
		}
		$warehouse->fetch($id);
		return $warehouse;
	}
}
