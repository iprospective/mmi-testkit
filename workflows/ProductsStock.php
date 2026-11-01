<?php
namespace Mmi\TestKit\Workflows;

use Mmi\TestKit\TestCase;

class ProductsStock
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
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';

		$t = $this->t;
		$fixtures = new \Mmi\TestKit\Fixtures\FixtureBuilder($this->db, $this->user, $this->ctx['mysoc']);
		$product = $fixtures->ensureProduct(20.0, 'ZZSTOCK20', 'Produit stock', 50.0);
		$t->ok('produit de test créé', $product !== null);
		if (!$product) {
			return;
		}

		$warehouse = $this->ensureWarehouse();
		$t->ok('entrepôt de test disponible', $warehouse !== null);
		if (!$warehouse) {
			return;
		}

		$t->etape('1. Mouvement de stock');
		$before = $this->stock($product->id, $warehouse->id);
		$res = $product->correct_stock($this->user, $warehouse->id, 10, 0, 'Test stock');
		$t->ok('mouvement de stock créé', $res > 0);
		$stock = $this->stock($product->id, $warehouse->id);
		$t->checkClose('stock du produit et de l’entrepôt', $stock, $before + 10, 0.001);

		$res = $product->correct_stock($this->user, $warehouse->id, 2, 1, 'Sortie test');
		$t->ok('sortie de stock créée', $res > 0);
		$stock = $this->stock($product->id, $warehouse->id);
		$t->checkClose('stock après sortie', $stock, $before + 8, 0.001);

		$resql = $this->db->query('SELECT COUNT(*) as nb FROM '.MAIN_DB_PREFIX.'stock_mouvement WHERE fk_product = '.((int) $product->id));
		$obj = $resql ? $this->db->fetch_object($resql) : null;
		$t->ok('mouvements de stock historisés', $obj && (int) $obj->nb >= 2);

		$t->etape('2. Lot / DDM');
		$lot = new \ProductLot($this->db);
		$lot->fk_product = $product->id;
		$lot->batch = 'ZZLOT'.substr((string) dol_now(), -6);
		$lot->eatby = dol_now() + 86400 * 30;
		$lot->sellby = dol_now() + 86400 * 20;
		$idlot = $lot->create($this->user);
		$t->ok('lot créé', $idlot > 0);
		if ($idlot > 0) {
			$lot->fetch($idlot);
			$t->check('lot rattaché au produit', (int) $lot->fk_product, (int) $product->id);
			$t->ok('lot avec date DDM', !empty($lot->eatby));
		}
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
