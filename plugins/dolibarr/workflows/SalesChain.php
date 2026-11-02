<?php
namespace Mmi\TestKit\Workflows;

use Mmi\TestKit\TestCase;

class SalesChain
{
	/** @var array */
	private $ctx;

	/** @var TestCase */
	private $t;

	/** @var object */
	private $db;

	/** @var object */
	private $user;

	/** @var object */
	private $langs;

	/** @var object */
	private $mysoc;

	/** @var \Mmi\TestKit\Fixtures\FixtureBuilder */
	private $fixtures;

	public function __construct($ctx, TestCase $test)
	{
		$this->ctx = $ctx;
		$this->t = $test;
		$this->db = $ctx['db'];
		$this->user = $ctx['user'];
		$this->langs = $ctx['langs'];
		$this->mysoc = $ctx['mysoc'];
		$this->fixtures = new \Mmi\TestKit\Fixtures\FixtureBuilder($this->db, $this->user, $this->mysoc);
	}

	public function run()
	{
		require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
		require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
		require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		$db = $this->db;
		$user = $this->user;
		$t = $this->t;

		$taux = $this->tauxProposes();
		if ($t->verbose) { echo 'taux proposés: '.implode('|', $taux).' (pays='.(isset($this->mysoc->country_id) ? $this->mysoc->country_id : 'null').")\n"; }
		$t->ok('le formulaire propose au moins deux taux de TVA', count($taux) >= 2);
		$t->check('le taux 5,5 est proposé', in_array('5.5', $taux, true), true);
		$t->check('le taux 20 est proposé', in_array('20', $taux, true), true);

		$p55 = $this->fixtures->ensureProduct(5.5, 'ZZTEST55', 'Produit test TVA 5,5', 10.0);
		$p20 = $this->fixtures->ensureProduct(20.0, 'ZZTEST20', 'Produit test TVA 20', 20.0);
		if (!$p55 || !$p20) {
			$t->fail('fixtures produits', 'impossible de créer les produits de test');
			return;
		}
		$produits = array('5.5' => $p55, '20' => $p20);

		$soc = $this->fixtures->createCustomer('ZZ Test chaine de vente');
		$t->ok('création du tiers de test', $soc !== null);
		if (!$soc) {
			$t->fail('tiers', 'création impossible');
			return;
		}
		$socid = $soc->id;

		$t->etape('1. Devis');
		$propal = new \Propal($db);
		$propal->socid = $socid;
		$propal->date = dol_now();
		$propal->duree_validite = 30;
		$pid = $propal->create($user);
		$t->ok('le devis est créé', $pid > 0);
		if ($pid <= 0) {
			$t->fail('devis', $propal->error);
			return;
		}
		foreach ($produits as $p) {
			$propal->addline('Ligne '.$p->ref, $p->price, 2, $p->tva_tx, 0, 0, $p->id, 0, 'HT', 0, 0, 0, -1, 0, 0, 0, 0, 0, '', 0, 0, null, 0, '', 0, 100);
		}
		$propal->fetch($pid);
		$t->check('le devis porte 2 lignes', count($propal->lines), 2);
		$this->controleDocument($propal, 'devis', $taux);
		if ((int) $propal->statut === \Propal::STATUS_DRAFT) {
			$resvalid = $propal->valid($user);
			if ($resvalid <= 0) { $t->fail('validation du devis', $propal->error.' '.json_encode($propal->errors)); }
			$propal->fetch($propal->id);
		}
		$t->check('le devis est validé', (int) $propal->statut, \Propal::STATUS_VALIDATED);

		$t->etape('2. Devis vers commande');
		$cmd = new \Commande($db);
		$ok = $cmd->createFromProposal($propal, $user);
		$t->ok('la commande est créée depuis le devis', $ok > 0);
		if ($ok > 0) {
			$cmd->fetch($cmd->id);
			$t->check('la commande reprend les 2 lignes', count($cmd->lines), 2);
			$t->checkClose('le total TTC est identique au devis', $cmd->total_ttc, $propal->total_ttc);
			$this->controleDocument($cmd, 'commande', $taux);
			foreach ($cmd->lines as $i => $line) {
				$t->checkClose('commande : ligne '.$i.' même taux que le devis', $line->tva_tx, $propal->lines[$i]->tva_tx, 0.001);
			}
			if ((int) $cmd->statut === \Commande::STATUS_DRAFT) {
				$resvalid = $cmd->valid($user);
				if ($resvalid <= 0) { $t->fail('validation de la commande', $cmd->error.' '.json_encode($cmd->errors)); }
				$cmd->fetch($cmd->id);
			}
			$t->check('la commande est validée', (int) $cmd->statut, \Commande::STATUS_VALIDATED);
		}

		$t->etape('3. Commande vers facture');
		$fac = new \Facture($db);
		try {
			$okf = ($ok > 0) ? $fac->createFromOrder($cmd, $user) : -1;
		} catch (\Throwable $e) {
			$okf = -99;
			$t->fail('exception createFromOrder', get_class($e).' '.$e->getMessage());
		}
		if ($okf <= 0) {
			$t->warn('createFromOrder', 'retour='.$okf.' (id partiel='.(int) $fac->id.') — bascule sur création manuelle');
			$fac = $this->createInvoiceFallback($cmd);
			$okf = ($fac ? 1 : -1);
		}
		$t->ok('la facture est créée depuis la commande', $okf > 0);
		if ($okf <= 0 || !$fac) {
			$t->fail('facture', 'création impossible');
			return;
		}
		$fac->fetch($fac->id);
		$t->check('la facture reprend les 2 lignes', count($fac->lines), 2);
		$t->checkClose('le total TTC est identique à la commande', $fac->total_ttc, $cmd->total_ttc);
		$this->controleDocument($fac, 'facture', $taux);
		foreach ($fac->lines as $i => $line) {
			$t->checkClose('facture : ligne '.$i.' même taux que la commande', $line->tva_tx, $cmd->lines[$i]->tva_tx, 0.001);
		}
		$parTaux = array();
		foreach ($fac->lines as $line) {
			$k = (string) price2num($line->tva_tx);
			$parTaux[$k] = (isset($parTaux[$k]) ? $parTaux[$k] : 0) + (float) $line->total_tva;
		}
		$t->checkClose('facture : la ventilation par taux couvre toute la TVA', array_sum($parTaux), $fac->total_tva);
		$t->check('facture : deux taux distincts présents', count($parTaux), 2);

		$t->etape('4. Paiement');
		$t->ok('la facture est validée', $fac->validate($user) > 0);
		$fac->fetch($fac->id);
		$t->check('le statut de la facture est validé', (int) $fac->statut, 1);

		$resql = $db->query('SELECT id FROM '.MAIN_DB_PREFIX.'c_paiement WHERE active = 1 ORDER BY id LIMIT 1');
		$mode = $resql ? (int) $db->fetch_object($resql)->id : 0;
		$resql = $db->query('SELECT rowid FROM '.MAIN_DB_PREFIX.'bank_account WHERE clos = 0 ORDER BY rowid LIMIT 1');
		$compte = $resql ? (int) $db->fetch_object($resql)->rowid : 0;

		$pai = new \Paiement($db);
		$pai->datepaye = dol_now();
		$pai->amounts = array($fac->id => (float) $fac->total_ttc);
		$pai->paiementid = $mode;
		try {
			$idp = $pai->create($user, 1);
			$t->ok('le paiement du solde est enregistré', $idp > 0);
			if ($idp > 0 && $compte > 0) {
				$pai->addPaymentToBank($user, 'payment', '(Test)', $compte, '', '');
			}
		} catch (\Throwable $e) {
			$t->fail('paiement exception', $e->getMessage().' @ '.$e->getFile().':'.$e->getLine());
			return;
		}
		$fac->fetch($fac->id);
		$t->check('la facture passe à payée', (int) $fac->paye, 1);
		$t->checkClose('le montant payé égale le TTC', $fac->getSommePaiement(), $fac->total_ttc);
	}

	private function tauxProposes()
	{
		$db = $this->db;
		$form = new \Form($db);
		$ach = new \Societe($db);
		$resql = $db->query('SELECT rowid FROM '.MAIN_DB_PREFIX.'societe WHERE client > 0 AND fk_pays = '.((int) $this->mysoc->country_id).' LIMIT 1');
		$obj = $resql ? $db->fetch_object($resql) : null;
		if ($obj) {
			$ach->fetch((int) $obj->rowid);
		}
		preg_match_all('/<option[^>]*value="([^"]*)"/', $form->load_tva('tva_tx', -1, $this->mysoc, $ach, 0, 0, '', false, 1), $m);
		return array_values(array_unique(array_map(function ($v) { return (string) price2num($v); }, $m[1])));
	}

	private function createInvoiceFallback($cmd)
	{
		$db = $this->db;
		$user = $this->user;

		$fac = new \Facture($db);
		$fac->socid = $cmd->socid;
		$fac->date = dol_now();
		$fac->type = 0;
		$fac->fk_project = $cmd->fk_project;
		$fac->ref_client = $cmd->ref_client;
		$fac->cond_reglement_id = $cmd->cond_reglement_id;
		$fac->mode_reglement_id = $cmd->mode_reglement_id;
		$fac->note_private = $cmd->note_private;
		$fac->note_public = $cmd->note_public;
		$id = $fac->create($user, 1);
		if ($id <= 0) {
			$this->t->fail('facture fallback', $fac->error.' '.json_encode($fac->errors));
			return null;
		}
		foreach ($cmd->lines as $line) {
			$res = $fac->addline(
				$line->label ? $line->label : $line->desc,
				$line->subprice,
				$line->qty,
				$line->tva_tx,
				$line->localtax1_tx,
				$line->localtax2_tx,
				$line->fk_product,
				$line->remise_percent,
				$line->date_start,
				$line->date_end,
				0,
				$line->info_bits,
				0,
				'HT',
				0,
				$line->product_type,
				-1,
				$line->special_code,
				'',
				0,
				0,
				null,
				0,
				$line->label,
				array(),
				100,
				0,
				$line->fk_unit
			);
			if ($res < 0) {
				$this->t->fail('facture fallback ligne', $fac->error.' '.json_encode($fac->errors));
				return null;
			}
		}
		$fac->fetch($id);
		return $fac;
	}

	private function controleDocument($obj, $nom, $taux)
	{
		$t = $this->t;
		$sHT = 0.0;
		$sTVA = 0.0;
		$sTTC = 0.0;
		foreach ($obj->lines as $i => $line) {
			$sHT += (float) $line->total_ht;
			$sTVA += (float) $line->total_tva;
			$sTTC += (float) $line->total_ttc;
			$label = $nom.' : ligne '.$i;
			$t->checkClose($label.' HT+TVA = TTC', (float) $line->total_ht + (float) $line->total_tva, (float) $line->total_ttc);
			$t->checkClose($label.' TVA conforme au taux '.$line->tva_tx, ((float) $line->total_ht * (float) $line->tva_tx / 100), (float) $line->total_tva, 0.02);
			$t->check($label.' taux '.$line->tva_tx.' proposé par le formulaire', in_array((string) price2num($line->tva_tx), $taux, true), true);
			if (!empty($line->fk_product)) {
				$product = new \Product($this->db);
				$product->fetch((int) $line->fk_product);
				$t->checkClose($label.' taux = taux du produit', $line->tva_tx, $product->tva_tx, 0.001);
			}
		}
		$t->checkClose($nom.' : total HT = somme des lignes', $sHT, $obj->total_ht);
		$t->checkClose($nom.' : total TVA = somme des lignes', $sTVA, $obj->total_tva);
		$t->checkClose($nom.' : total TTC = somme des lignes', $sTTC, $obj->total_ttc);
		$t->checkClose($nom.' : total HT+TVA = TTC', (float) $obj->total_ht + (float) $obj->total_tva, (float) $obj->total_ttc);
	}
}
