<?php
namespace Mmi\TestKit\Fixtures;

use Product;
use Societe;

/**
 * Fixtures de base réutilisables par les workflows.
 *
 * Les fixtures spécifiques aux modules vivront dans <module>/test/fixtures/ ;
 * cette classe ne couvre que le socle commun (tiers, produits, TVA).
 */
class FixtureBuilder
{
	/** @var object */
	private $db;

	/** @var object */
	private $user;

	/** @var object */
	private $mysoc;

	public function __construct($db, $user, $mysoc)
	{
		$this->db = $db;
		$this->user = $user;
		$this->mysoc = $mysoc;
	}

	/**
	 * Crée un tiers client de test.
	 *
	 * @param string $label
	 * @return Societe|null
	 */
	public function createCustomer($label)
	{
		$soc = new Societe($this->db);
		$soc->name = $label.' '.dol_print_date(dol_now(), '%Y%m%d%H%M%S');
		$soc->client = 1;
		$soc->status = 1;
		$soc->country_id = $this->mysoc->country_id;
		$soc->email = 'zz-test@recette.local';
		$soc->get_codeclient($soc, 0);
		$soc->code_compta_client = '411ZZ'.substr((string) dol_now(), -6);
		$id = $soc->create($this->user);
		if ($id <= 0) {
			return null;
		}
		$soc->fetch($id);
		return $soc;
	}

	/**
	 * Crée (ou récupère) un produit de test au taux demandé.
	 *
	 * @param float  $taux
	 * @param string $ref
	 * @param string $label
	 * @param float  $price
	 * @return Product|null
	 */
	public function ensureProduct($taux, $ref, $label, $price)
	{
		$product = new Product($this->db);
		if ($product->fetch(0, $ref) > 0) {
			return $product;
		}
		$product->ref = $ref;
		$product->label = $label;
		$product->type = 0;
		$product->status = 1;
		$product->tosell = 1;
		$product->tva_tx = $taux;
		$product->price = $price;
		$product->price_base_type = 'HT';
		$id = $product->create($this->user);
		if ($id <= 0) {
			return null;
		}
		$product->fetch($id);
		$product->price = $price;
		$product->price_base_type = 'HT';
		$product->update($id, $this->user);
		return $product;
	}
}
