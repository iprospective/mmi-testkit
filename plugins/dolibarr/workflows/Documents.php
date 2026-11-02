<?php
namespace Mmi\TestKit\Workflows;

use Mmi\TestKit\TestCase;

class Documents
{
	private $ctx;
	private $t;
	private $db;
	private $user;
	private $langs;
	private $conf;
	private $fixtures;

	public function __construct($ctx, TestCase $test)
	{
		$this->ctx = $ctx;
		$this->t = $test;
		$this->db = $ctx['db'];
		$this->user = $ctx['user'];
		$this->langs = $ctx['langs'];
		$this->conf = $ctx['conf'];
		$this->fixtures = new \Mmi\TestKit\Fixtures\FixtureBuilder($this->db, $this->user, $ctx['mysoc']);
	}

	public function run()
	{
		require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

		$soc = $this->fixtures->createCustomer('ZZ Documents');
		$t = $this->t;
		$t->ok('tiers de test créé', $soc !== null);
		if (!$soc) {
			return;
		}
		$product = $this->fixtures->ensureProduct(20.0, 'ZZDOC20', 'Produit documents', 100.0);
		$t->ok('produit de test créé', $product !== null);
		if (!$product) {
			return;
		}

		$generated = array();

		$t->etape('1. Commande et PDF');
		$cmd = new \Commande($this->db);
		$cmd->socid = $soc->id;
		$cmd->date = dol_now();
		$id = $cmd->create($this->user);
		$t->ok('commande créée', $id > 0);
		if ($id > 0) {
			$cmd->addline('Ligne document', 100, 1, 20, 0, 0, $product->id);
			$cmd->fetch($id);
			$t->ok('commande validée', $cmd->valid($this->user) > 0);
			$cmd->fetch($id);
			$model = getDolGlobalString('COMMANDE_ADDON_PDF', 'eratosthene');
			$res = $cmd->generateDocument($model, $this->langs);
			$t->ok('PDF commande généré', $res > 0);
			$generated[] = $this->checkDocumentFile($cmd, 'commande');
		}

		$t->etape('2. Facture et PDF');
		$fac = new \Facture($this->db);
		$fac->socid = $soc->id;
		$fac->date = dol_now();
		$fac->type = 0;
		$idf = $fac->create($this->user, 1);
		$t->ok('facture créée', $idf > 0);
		if ($idf > 0) {
			$fac->addline('Ligne facture', 100, 1, 20, 0, 0, $product->id);
			$fac->fetch($idf);
			$t->ok('facture validée', $fac->validate($this->user) > 0);
			$fac->fetch($idf);
			$model = getDolGlobalString('FACTURE_ADDON_PDF', 'sponge');
			$res = $fac->generateDocument($model, $this->langs);
			$t->ok('PDF facture généré', $res > 0);
			$generated[] = $this->checkDocumentFile($fac, 'facture');
		}

		$t->etape('3. Expédition et PDF');
		if ($id > 0) {
			$warehouse = $this->ensureWarehouse();
			if ($warehouse) {
				$product->correct_stock($this->user, $warehouse->id, 10, 0, 'Stock document');
				$cmd->fetch($id);
				$exp = new \Expedition($this->db);
				$exp->socid = $soc->id;
				$exp->origin = 'commande';
				$exp->origin_id = $cmd->id;
				$rline = $exp->addline($warehouse->id, $cmd->lines[0]->id, 1);
				$t->ok('ligne d’expédition ajoutée', $rline > 0);
				$ide = $rline > 0 ? $exp->create($this->user) : -1;
				$t->ok('expédition créée', $ide > 0);
				if ($ide <= 0) { $t->fail('expédition', $exp->error.' '.json_encode($exp->errors)); }
				if ($ide > 0) {
					$exp->fetch($ide);
					$t->ok('expédition validée', $exp->valid($this->user) > 0);
					$exp->fetch($ide);
					$model = getDolGlobalString('EXPEDITION_ADDON_PDF', 'espadon');
					$res = $exp->generateDocument($model, $this->langs);
					$t->ok('PDF expédition généré', $res > 0);
					$generated[] = $this->checkDocumentFile($exp, 'expedition');
				}
			}
		}

		foreach ($generated as $file) {
			if ($file) {
				@unlink($file);
			}
		}
	}

	private function checkDocumentFile($object, $label)
	{
		$relative = isset($object->last_main_doc) ? $object->last_main_doc : '';
		$this->t->ok($label.' : last_main_doc renseigné', $relative !== '');
		if ($relative === '') {
			return null;
		}
		$path = DOL_DATA_ROOT.'/'.$relative;
		$this->t->ok($label.' : fichier PDF présent', is_file($path));
		$this->t->ok($label.' : PDF non vide', is_file($path) && filesize($path) > 100);
		return is_file($path) ? $path : null;
	}

	private function ensureWarehouse()
	{
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
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
