# Anomalies et points ouverts — TestKit / envs 19-20

> État au 2026-11-01. Les contournements marqués « env » ne sont **pas** dans le
> code des forks/modules ; ils sont appliqués uniquement dans les environnements de test.

## A1 — `Facture::createFromOrder()` retourne -1 après création partielle

- **Symptôme** : la facture est insérée (id non nul), mais la méthode renvoie `-1`.
- **Effet** : le workflow S1 bascule sur une création manuelle et émet un avertissement.
- **Piste** : copie des extrafields/liaisons de la commande vers la facture ; champs
  absents côté facture (`expe_ok`, `palier`, `fk_product_lot`) et/ou échec de liaison.
- **Contournement env** : champs ajoutés dans les bases de test ; fallback S1.
- **Correctif durable** : filtrer les extrafields copiés dans `createFromOrder`, ou
  déclarer les champs côté facture dans les modules concernés.

## A2 — `pdf_sponge` incompatible PHP >= 8.1

- **Symptôme** : `round(): Argument #1 ($num) must be of type int|float, string given`.
- **Lignes** : `core/modules/facture/doc/pdf_sponge.modules.php:849` (19.0) et `:886`
  (20.0).
- **Effet** : paiement/PDF en échec sur PHP 8.1+ sur 19.0-mmi ; 20.0.5 passe avec le
  correctif appliqué dans l’env.
- **Correctif proposé** : `round((float) pdf_getlinetotalexcltax(...), 2)`.
- **Statut** : appliqué dans les envs de test uniquement ; à porter dans le fork.

## A3 — Extrafields commande/facture incohérents

- `expe_ok` existe sur `commande` mais pas sur `facture`.
- `palier` et `fk_product_lot` existent sur `commandedet` mais pas sur `facturedet`.
- **Effet** : la conversion commande → facture échoue lors de l’insertion des
  extrafields.
- **Contournement env** : colonnes + définitions ajoutées dans `facture` / `facturedet`.
- **Correctif durable** : filtrage dans le cœur ou déclaration côté cible par les
  modules (`mmiproductdluo`, `mmiworkflow`).

## A4 — `mmiworkflow::pdf_build_address` avec `$object->commande` null

- **Symptôme** : `Call to a member function getIdContact() on null`
  (`custom/mmiworkflow/class/actions_mmiworkflow.class.php:381`).
- **Déclencheur** : génération PDF d’une expédition dont l’objet `commande` n’est pas
  chargé.
- **Contournement env** : garde `!empty($object->commande) && is_object(...)`.
- **Correctif durable** : porter la garde dans le module `mmiworkflow` (branche 20.0).

## A5 — `mmiprestasync` : destructeur et configuration

- Le destructeur `MMI_Notification` ouvre une connexion `mysqli` avec
  `$GLOBALS['dolibarr_main_db_*']` ; en CLI, ces globals n’étaient pas définis.
- **Contournement** : le bootstrap du TestKit expose désormais les globals DB.
- **Reste** : table de file `_sync` absente et `MMIPRESTASYNC_WS_SYNC_PASS` non
  configuré dans les envs ; S6 est donc un **contrat local**, pas une synchro réelle.
- **Recommandation** : ne pas ouvrir de connexion dans le destructeur si la config est
  absente ; test de synchro à faire avec un PrestaShop de recette.

## A6 — Configuration stock nécessaire aux tests d’expédition

- `STOCK_CALCULATE_ON_SHIPMENT=1` et `STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT=1` doivent être
  activés pour exercer la décrémentation et le contrôle de stock.
- L’entrepôt doit être `statut=1`.
- **Statut** : configuré dans les bases de test ; à documenter dans le provisioning.

## A7 — Isolation des tests / commits internes

- Certaines méthodes Dolibarr (`create`, `correct_stock`, `valid`) commitent en interne ;
  la transaction du runner ne suffit pas toujours.
- **Conséquence** : des données `ZZ*` persistent (entrepôt, produits, stocks).
- **Contournement** : références uniques, deltas de stock, garde d’entrepôt existant.
- **À faire** : reset de base (dump/restore) ou base dédiée par run pour une
  reproductibilité stricte.

## A8 — Matrice PHP

- **20.0-mmi-merge** : S1 verte sur PHP **7.4, 8.1, 8.2, 8.3, 8.4** (avec correctif
  `pdf_sponge` appliqué dans l’env).
- **19.0-mmi** : S1 verte sur **7.4** ; échec sur **8.1, 8.2, 8.3, 8.4** dû à
  `pdf_sponge:849` (A2).
- **Recommandation** : 8.3/8.4 pour le fork 20 après correction core ; 7.4 reste la
  référence 19 tant que A2 n’est pas corrigé.

## A9 — S6 / S7 : couverture partielle

- **S6** : module `mmiprestasync` activé, hooks/triggers vérifiés, mais pas de
  PrestaShop ni de secret → synchro non exercée.
- **S7** : droits utilisateur, échappement HTML vérifiés ; jeton CSRF non testable en
  CLI (session absente) → avertissement. Les tests HTTP/IHM restent à ajouter.

## A10 — Contournements d’environnement non commités

Appliqués dans `/home/siteadm/iprospective/public/dolibarr-test/` uniquement :

- colonnes/extrafields `facture` et `facturedet` ;
- correctif `pdf_sponge` (19 et 20) ;
- garde `mmiworkflow` ;
- constantes de stock, droits admin complets, `MMI_TEST_ENV`, mails désactivés ;
- `STOCK_*` et `statut` entrepôt.

Ils doivent être intégrés au provisioning reproductible ou aux branches source.

## A11 — Benchmark

- Le scénario « Calicote-like » est défini (`docs/plan-coverage.md`) mais **pas encore
  exécuté**.
- Reste : base `dolibarr_bench_19/20`, seeding bulk, mesures, baseline et seuils.

## A12 — Publication GitHub de `mmidevtools`

- Reportée ; GitLab reste la référence. Le remote `github` est configuré.

## A13 — Modules non couverts / limites

- S6 ne teste pas la synchro réelle (PrestaShop absent).
- S7 ne teste pas les pages HTTP, CSRF navigateur, uploads, MMISecurity (module non
  actif sur la base de test).
- Pas encore de tests `mmimail`, `mmiged`, `mmisav`, `mmiyounited`, `mmibrevo`,
  `mmiavisverifies`, `mmiredmine`, `mmiproject`, `mmiatelier`, `BTP`, `nomenclature`.
- Pas de test multi-modules composé (installation vide + N modules) dans cette campagne.
