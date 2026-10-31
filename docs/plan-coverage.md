# Plan de couverture — première phase

**Périmètre** : Calicote, Calyclay, Pisceen et l'instance MMI (compta).
**Objectif** : couvrir d'abord ce qui est réellement utilisé, puis étendre aux autres
clients et modules.

## 1. Sources et méthode

Mesures faites le **2026-10-31** sur les clones locaux des bases de production
(lecture seule). Les volumes sont des `COUNT(*)` exacts sur les tables clés ; les
tables de modules sont comptées via `information_schema` quand elles existent.

| Instance | Base locale | Version | Taille |
|---|---|---|---|
| Calicote | `calicote_dolibarr` (préfixe `llx_`) | 19.0.4 | ~1,0 Go |
| Calyclay | `calyclay_doli966` (préfixe `llxsq_`) | 19.0.4 (installé 11.0.2) | ~38 Mo |
| Pisceen | `pisceen_erp` (préfixe `llx_`) | 19.0.4 | ~2,6 Go |
| MMI (compta) | `dolibarr_compta_test` (préfixe `llx_`) | 19.0.4 | ~38 Mo |

> Les clones peuvent avoir quelques heures ou jours de retard sur la prod. Avant
> d’enregistrer une baseline, refaire les mêmes comptages en lecture seule sur la prod.

## 2. Ce que disent les bases

### 2.1 Volumes par instance

| Table | Calicote | Calyclay | Pisceen | MMI |
|---|---:|---:|---:|---:|
| `societe` | 12 647 | 139 | 38 815 | 64 |
| `socpeople` | 23 861 | 8 | 40 575 | 31 |
| `product` | 2 290 | 1 437 | 4 699 | 68 |
| `propal` | 11 | 7 | **46 652** | 7 |
| `propaldet` | 49 | 56 | **238 448** | 37 |
| `commande` | **18 798** | 13 | 9 809 | 8 |
| `commandedet` | **170 100** | 70 | 44 440 | 37 |
| `facture` | **16 203** | 18 | 9 239 | 212 |
| `facturedet` | **141 269** | 78 | 42 907 | 1 068 |
| `expedition` | **17 304** | 4 | 7 792 | 0 |
| `expeditiondet` | 146 955 | 3 | 13 395 | 0 |
| `expeditiondet_batch` | **147 202** | 1 | 0 | 0 |
| `reception` | 1 591 | 1 | 4 095 | 0 |
| `paiement` | 16 023 | 2 | 12 750 | 164 |
| `paiement_facture` | 16 126 | 2 | 12 518 | 190 |
| `product_lot` | **10 765** | 3 | 0 | 0 |
| `stock_mouvement` | **178 286** | 181 | 25 015 | 0 |
| `accounting_bookkeeping` | 61 414 | 0 | 33 509 | 0 |
| `actioncomm` | 128 606 | 7 144 | **631 629** | 1 894 |
| `ecm_files` | 45 402 | 373 | 109 180 | 714 |
| `product_fournisseur_price` | 2 211 | 1 167 | 5 143 | 22 |
| `mmimail_message` | 0 | 0 | **77 324** | 0 |
| `mbi_etransactions_hash` | 116 | 0 | **36 660** | 0 |
| `younitedpay` | 0 | 0 | 144 | 0 |
| `_sync` | **544 920** | — | 48 716 | — |
| `projet` | 0 | **27** | 5 | 23 |
| `projet_task` | 0 | **186** | 0 | 1 |
| `ticket` | 0 | 0 | 29 | 0 |
| `facture_fourn` | 3 | 1 | 6 | **325** |
| `commande_fournisseur` | 1 542 | 2 | 5 507 | 1 |

Volumes spécifiques complémentaires :

- **Calicote** : `product_stats` 295 590, `product_stock_stats` **2 457 062**,
  `actioncomm_resources` 142 097, `element_contact` 59 244, `ecm_files` 45 402,
  `bank_url` 31 432, lots 10 765, expéditions avec lots 147 202.
- **Calyclay** : nomenclature 43 / 25 lignes, MRP production 25, BOM 5 / 13 lignes,
  contrats 1, congés 11, `element_time` 119, `product_fournisseur_price` 1 167.
- **Pisceen** : `mmimail_message_link` 311 503, `mmimail_recipient` 81 416,
  `mmimail_body` 70 806, `mmimail_address` 28 932, MBI retours 8 310,
  `younitedpay_webhook_log` 163, `propale_history` **56 166**, BOM 5 / 29, MRP 10.
- **MMI** : `facture_fourn` 325 / 563 lignes, `payment_various` 448,
  `salary` 154, `payment_salary` 154, projets 23, ECM 714.

### 2.2 Modules MMI actifs

| Module | Calicote | Calyclay | Pisceen | MMI |
|---|:---:|:---:|:---:|:---:|
| MMICommon | ✓ | ✓ | ✓ | ✓ |
| MMICompta | ✓ | ✓ | ✓ | ✓ |
| MMIDocuments | ✓ | ✓ | ✓ | ✓ |
| MMIProduct | ✓ | ✓ | ✓ | — |
| MMIProductDluo | ✓ | — | — | — |
| MMIPayments | ✓ | ✓ | ✓ | — |
| MBIETransactions | ✓ | ✓ | ✓ | — |
| MMIPrestaSync | ✓ | ✓ | ✓ | — |
| MMIWorkflow | ✓ | ✓ | ✓ | — |
| MMIShipping | ✓ | ✓ | ✓ | — |
| MMIEntrepots | ✓ | — | ✓ | — |
| MMIFilters | ✓ | — | ✓ | — |
| MMIFournisseurPrice | ✓ | ✓ | ✓ | — |
| MMICRM | ✓ | ✓ | ✓ | — |
| MMIStats | ✓ | ✓ | ✓ | — |
| MMIProject | — | ✓ | — | — |
| MMIAtelier | — | ✓ | — | — |
| MMIGED | — | — | ✓ | — |
| MMIMAIL | — | — | ✓ | — |
| MMISAV | — | — | ✓ | — |
| MMISecurity | — | — | ✓ | — |
| MMIVATEEC | — | — | ✓ | — |
| MMIYounited | — | — | ✓ | — |
| MMIBrevo | — | — | ✓ | — |
| MMIAvisVerifies | — | — | ✓ | — |
| MMIRedmine | — | — | ✓ | — |
| MMIWildx | — | — | ✓ | — |

Modules cœur/custom notables, en plus : `PropaleHistory` (Calicote, Pisceen, MMI),
`BOM`/`MRP` (Calyclay, Pisceen), `Nomenclature`/`BTP` (Calyclay), `Ticket`/`Recruitment`
(Pisceen/Calyclay), `Contrat`/`Holiday` (Calyclay), `API`/`Cron` (toutes),
`Mail`/`ExpenseReport`/`Salary` (Pisceen/MMI), `ECM` (toutes).

### 2.3 Lecture

- **Pisceen** est l’instance la plus complète : devis (46 k), mail (77 k messages,
  311 k liens), paiements CB (36 k), sync, ECM (109 k), SAV, TVA, Redmine. C’est la
  référence pour les tests d’intégration et de performance.
- **Calicote** est l’instance la plus volumineuse en transactionnel : commandes (18 k),
  factures (16 k), expéditions (17 k), lots (10 k), mouvements de stock (178 k),
  statistiques produit (2,4 M). C’est la référence pour les lots, le picking, les PDF
  et les performances de listes.
- **Calyclay** est petite mais large fonctionnellement : projets/tâches, atelier,
  BTP, nomenclature, MRP/BOM, prix/marge, congés. C’est la référence pour les modules
  projet/atelier/production et la marge.
- **MMI (compta)** est centrée sur la comptabilité, les factures fournisseurs, les
  projets, les salaires et les notes de frais ; peu de ventes et pas de stock. C’est la
  référence pour MMICompta, les documents et PropaleHistory.

## 3. Stratégie de couverture

Quatre niveaux, exécutés dans cet ordre :

1. **P0 — socle commun** : ce qui est utilisé par au moins trois instances ou qui
   touche le chiffre d’affaires, les documents, le stock et les paiements.
2. **P1 — contrat de module** : vérifications génériques pour chaque module actif
   (descripteur, SQL, droits, menus, hooks, triggers, config, pages, cron, API).
3. **P2 — spécifiques client** : les modules à forte valeur d’une instance
   (MMIMAIL, MMIGED, MMIYounited, MMIProject/Atelier, BTP, Nomenclature, MMICompta…).
4. **P3 — post-déploiement et quotidien** : hooks en base, menus, options, droits,
   gitlinks, cohérence des commandes récentes, go/no-go.

## 4. Suites P0

### S1 — Chaîne de vente

Devis → commande → facture → paiement, sur deux taux de TVA différents.

Cas : totaux HT/TVA/TTC ligne et document ; taux réellement proposé par le
formulaire ; taux du produit ; report des taux entre documents ; ventilation par taux ;
acomptes ; avoirs ; remises ; paiement partiel/solde ; facture de situation.

Cibles : toutes les instances. Priorité : Calicote, Pisceen, Calyclay, MMI.

### S2 — Documents et PDF

Génération PDF/ODT des devis, commandes, factures, expéditions, réceptions et factures
fournisseurs. Modèles du fork (`pdf_sponge`, `pdf_crabe`, `pdf_octopus`,
`doc_generic_invoice_odt`), mentions de TVA, champs complémentaires, renommage des PDF,
substitutions ODT, adresse de réception, tri des lignes.

Cibles : toutes. Spécifiques : Calicote/Calyclay (renommage, situation, mentions),
Pisceen (documents GED, factures fournisseurs).

### S3 — Produits, prix, marges et stock

Création/modification produit, prix de vente, marge, prix fournisseur, franco,
validité, prix par bande, stock simple, stock composé, mouvements, inventaire,
régularisation, lots DDM/DLUO, antigaspi, picking.

Cibles : Calicote (lots, picking, 2,4 M de stats), Calyclay (marge, prix fournisseur,
nomenclature), Pisceen (prix fournisseur, stock, BOM/MRP), MMI (peu de stock).

### S4 — Expédition et réception

Création d’expédition depuis commande, picking des lots, clôture, réception
fournisseur, dispatch, DF, port, autoliquidation, coût transporteur.

Cibles : Calicote (17 k expéditions, 147 k lignes de lots), Pisceen (7,8 k expéditions,
4 k réceptions), Calyclay (volumes faibles).

### S5 — Paiements

Paiement manuel, virement, chèque, CB Monetico/MBIE, Younited, paiements partiels,
tentatives, rapprochement, ventilation, remboursement/avoir.

Cibles : Calicote (MBIE), Calyclay (MBIE), Pisceen (MBIE + Younited, 36 k transactions),
MMI (paiements fournisseurs et divers).

### S6 — Synchronisation PrestaShop

Produits, clients, adresses, commandes, factures, paiements, stocks, lots ; idempotence,
reprise, résilience, absence d’écriture vers la prod ; contrôle des paliers et lots.

Cibles : Calicote (sync lots/stock), Calyclay, Pisceen.

### S7 — Sécurité et permissions

Accès pages, droits par groupe, filtrage IP, secrets, CSRF, uploads, API, webhooks.
Vérifier les modules sensibles : MMISecurity, MMIPayments, MBIE, MMIYounited,
MMIPrestaSync, MMIMAIL.

Cibles : Pisceen en priorité, puis toutes.

### S8 — Performance et smoke

Listes volumineuses (factures, commandes, expéditions, mails, ECM, stats), pages
d’accueil, recherche produit, ouverture de fiche, génération PDF, sync. Seuils de temps
et absence d’erreur PHP.

Cibles : Calicote (stats stock, sync), Pisceen (mail, actioncomm, ECM).

## 5. Suites P1 par client

### Calicote

| Sujet | Modules / données | Suite |
|---|---|---|
| Lots, DDM/DLUO, antigaspi, picking | MMIProductDluo, ProductBatch, 10 765 lots | `lots_picking` |
| Expédition, DF, port, transporteur | MMIShipping, 17 304 expéditions | `shipping` |
| Workflow 1 clic, brouillon | MMIWorkflow | `workflow` |
| Sync produits/commandes/stocks/lots | MMIPrestaSync, `_sync` 545 k | `sync_presta` |
| Documents, situation, renommage, TVA | MMIDocuments | `documents` |
| Paiement CB Monetico | MBIETransactions | `payments_mbie` |
| Prix/marge produit | MMIProduct | `product_margin` |

### Pisceen

| Sujet | Modules / données | Suite |
|---|---|---|
| Mail : collecte, envoi, ACL, pièces jointes | MMIMAIL, 77 k messages | `mmimail` |
| GED : rattachement, liens produits, envoi | MMIGED, 109 k fichiers | `ged` |
| SAV : tickets, interventions, retours | MMISAV, 29 tickets | `sav` |
| Sécurité IP et droits | MMISecurity | `security` |
| TVA B2C, VIES, éco-participation | MMIVATEEC | `vat_eec` |
| Financement Younited | MMIYounited, 144 dossiers | `younited` |
| Brevo / Avis Vérifiés | MMIBrevo, MMIAvisVerifies | `brevo`, `avis` |
| Redmine | MMIRedmine | `redmine` |
| BOM / MRP | core BOM, MRP | `bom_mrp` |

### Calyclay

| Sujet | Modules / données | Suite |
|---|---|---|
| Projets, temps, planning, contrats | MMIProject, 27 projets / 186 tâches | `project_time` |
| Atelier | MMIAtelier | `atelier` |
| BTP | BTP | `btp` |
| Nomenclature, MRP, BOM | Nomenclature, MRP, BOM | `production` |
| Prix/marge, prix fournisseur, franco | MMIProduct, MMIFournisseurPrice | `product_margin` |
| Documents, renommage, situation | MMIDocuments | `documents` |
| Congés | Holiday | `holiday` |

### MMI (compta)

| Sujet | Modules / données | Suite |
|---|---|---|
| PCA, export comptable, factures payées, acomptes | MMICompta | `mmicompta` |
| Factures fournisseurs | core Fournisseur, 325 factures | `supplier_invoices` |
| Projets et temps | Projet, 23 projets | `project_time` |
| Salaires, notes de frais, paiements divers | Salary, ExpenseReport, PaymentVarious | `hr_expenses` |
| Documents et PropaleHistory | MMIDocuments, PropaleHistory | `documents` |

## 6. Suite P1 — contrat de module

Pour chaque module actif, une suite générique vérifie :

- descripteur : `numero`, `version`, `depends`, `rights`, `menus`, `hooks`, `triggers` ;
- SQL : tables et colonnes attendues présentes (migration jouée) ;
- activation/désactivation/réactivation : constantes, droits, menus, hooks recopiés ;
- pages : chaque URL de menu répond autrement que 404, sans erreur PHP ;
- permissions : accès autorisé/refusé selon le groupe ;
- configuration : constantes lues par le code créées ou documentées ;
- cron/API : entrées présentes, pas d’appel sortant non maîtrisé ;
- hooks : `return 0/1`, `resArray`/`resPrint`, pas de `die`/`exit`, erreurs via
  `$hookmanager->errors`.

Cette suite est générée à partir des descripteurs et s’exécute pour tous les modules
actifs de chaque instance.

## 7. P3 — post-déploiement et quotidien

### Post-déploiement

- hooks enregistrés en base (`MAIN_MODULE_<MODULE>_HOOKS`) ;
- options de module présentes ;
- menus et pages joignables ;
- droits et groupes conformes ;
- gitlinks alignés (superprojet/modules) ;
- cron et API configurés ;
- `blockedlog` sans anomalie.

### Contrôle quotidien (lecture seule)

Sur les commandes récentes : TVA contre celle du produit, prix contre le catalogue,
remise contre la bande du lot, palier/lot cohérents, total = somme des lignes, doublons
de commandes. Alerte en cas d’écart.

Cibles : Calicote et Pisceen en priorité, puis Calyclay et MMI.

## 8. Fixtures et environnements

- **Fixtures rejouables** par suite : client, produits à 5,5 / 20 / 0 %, lots couvrant
  les quatre bandes, produit sans lot, pack, catégories, TVA.
- **Base dédiée ou transaction annulée** : les suites qui écrivent refusent de démarrer
  hors recette ; transaction annulée par défaut.
- **Anonymisation** : les snapshots de prod sont anonymisés avant usage (sociétés,
  contacts, e-mails, téléphones, IBAN).
- **Guards** : `MAIN_DISABLE_ALL_MAILS`, sync PrestaShop coupée, PSP en sandbox,
  pas de cron, pas d’écriture vers un système externe.
- **Isolation** : une suite ne dépend pas de l’état laissé par une autre.

## 9. Manifeste et exécution

Exemple de manifeste par instance :

```yaml
site: calicote-dolibarr
env:
  name: recette
  code: /home/.../htdocs
  database: calicote_recette
suites:
  - {id: sales_chain, path: test/mmi/chaine_vente_test.php, level: integration}
  - {id: lots_picking, path: custom/mmiproductdluo/test/..., level: functional}
  - {id: documents, path: custom/mmidocuments/test/..., level: functional}
  - {id: sync_presta, path: test/mmi/sync_presta_test.php, level: integration}
checks:
  postdeploy: [hooks_registered, menus_reachable, module_options, rights, gitlinks]
```

CLI cible :

```bash
mmi-test --manifest manifests/calicote.yml --env recette --full
mmi-test --manifest manifests/calicote.yml --env recette --subset sales_chain,lots_picking
mmi-test --manifest manifests/pisceen.yml --env recette --ticket RM3515
```

Sorties : TAP/JUnit/Markdown, code de sortie 0/1, verdict go/no-go.

## 10. Jalons

| Jalon | Contenu | Critère |
|---|---|---|
| J1 | Squelette, bootstrap, `TestCase`, rapport TAP, manifeste, guards | une suite vide passe |
| J2 | S1 chaîne de vente + S2 documents | vert sur Calicote et Pisceen |
| J3 | S3 produits/stock + S4 expédition/réception | vert sur Calicote, Calyclay, Pisceen |
| J4 | S5 paiements + S6 sync + S7 sécurité | vert sur Pisceen et Calicote |
| J5 | Contrat de module + P1 Calicote | tous les modules Calicote couverts |
| J6 | P1 Pisceen | MMIMAIL, GED, SAV, TVA, Younited, Redmine |
| J7 | P1 Calyclay + MMI | projets, atelier, BTP, production, compta |
| J8 | P3 post-déploiement + quotidien + CI/MEP | go/no-go exploitable |

Estimation indicative : **3 à 4 semaines** pour la première phase, hors écriture des
tests les plus lourds (mail, sync, BTP) qui peuvent être découpés.

## 11. Risques et questions ouvertes

- **Volumes** : `product_stock_stats` (2,4 M), `_sync` (545 k), `actioncomm` (631 k),
  `mmimail` (77 k) imposent des tests de performance et des requêtes bornées.
- **Données réelles** : anonymisation obligatoire avant tout snapshot utilisé en recette.
- **Écritures** : transaction annulée ou base dédiée ; jamais d’écriture en prod.
- **Intégrations** : MBIE, Younited, Brevo, Avis Vérifiés, PrestaShop, IMAP/SMTP à
  isoler systématiquement.
- **Versions** : le testkit doit tourner sur 19.0.4 (prod actuelle) et 20.0.5 (cible)
  avant la montée v21→v24.
- **Choix de socle** : PHPUnit + bootstrap Dolibarr, ou runner maison conservé pour
  les suites CLI existantes ? Décision à prendre au jalon J1.
- **Façade `mmitest`** : utile pour lancer les tests depuis l’admin de recette, mais
  optionnelle ; ne doit jamais être déployée en prod.
