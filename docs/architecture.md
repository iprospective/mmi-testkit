# Architecture — MMI Dolibarr TestKit

> **Évolution cible** : le TestKit devient `mmi-testkit` (core + plugins) et s'appuie sur
> `mmi-kb` pour la connaissance produit. Voir [`cdc-mmi-kb.md`](cdc-mmi-kb.md) et
> [`HANDOFF-2026-11-01.md`](HANDOFF-2026-11-01.md).

## Contexte

Le protocole de test Calicote (RM3515) est né après l'incident TVA du 2026-10-21 : une
valeur correcte au niveau du code mais non exploitable par le formulaire a produit deux
factures erronées. Les tests existants vérifiaient le format, pas l'utilisabilité réelle.

Le besoin couvre :

- le **cœur** du fork (parcours de vente, caisse, expédition, contrôles de déploiement) ;
- les **modules MMI** (tests unitaires, fonctionnels, intégration) ;
- un **verdict go/no-go** avant MEP, rejouable ;
- des **contrôles post-déploiement** ;
- une partie humaine non automatisable (rendu, matériel, douchette).

Actifs existants à réutiliser :

- `test/mmi/chaine_vente_test.php` — chaîne de vente, transaction annulée ;
- `htdocs/custom/mmimail/test/run-tests.php` — runner et cas ;
- suites PHPUnit présentes dans plusieurs modules ;
- `prestashop/.mmi-pm/project/test-manifest.yml` + `pm-site-test.py` — modèle manifeste/runner ;
- norme PM `modules/testing.md` — parcours, enchaînements, cas limites, protocole humain.

## Décision

**Outil indépendant, tests colocalisés, façade module optionnelle.**

1. **`mmi-dolibarr-testkit`** : dépôt autonome, CLI et bibliothèque PHP, installé uniquement
   en recette. Il ne s'agit ni d'un module Dolibarr ni d'une dépendance de `mmicommon`.
2. **Tests colocalisés** : le cœur/transverse dans `test/mmi/` du fork ; chaque module
   dans son propre `test/`. Ils sont versionnés avec le code qu'ils testent.
3. **Manifeste + runner** : un fichier YAML décrit les suites, les environnements, les
   échantillons et les attentes ; le runner produit un rapport et un verdict.
4. **Façade optionnelle `mmidevtools`** : si un écran d'admin est souhaité, un module
   dédié *recette uniquement*, sans dépendance, appelle le runner. Il ne contient pas
   les tests. Ce module porte aussi les templates canoniques de documentation
   (`gitlab:dolibarr/dolibarr-module-mmidevtools`).

### Alternatives écartées

| Option | Pourquoi non |
|---|---|
| Framework dans `mmicommon` | `mmicommon` est chargé en production et dépendance de 27 modules ; surface et couplage inacceptables. Impossible de tester `mmicommon` avec lui-même. |
| Module Dolibarr dédié au framework | Install/activation/tables/hooks pour du code qui ne doit jamais être déployé ; les tests HTTP/UI ne rentrent pas dans un module. |
| Scripts ad hoc par ticket | Duplication, pas de verdict commun, pas de rejouabilité, pas de garde-fous centralisés. |
| Tout dans le repo PM | Mélange outillage et gestion de projet ; difficile à versionner et à distribuer. |

## Composants

```text
bin/mmi-test
src/
  Bootstrap/        # chargement conf.php + master.inc.php
  TestCase/         # assertions, cycle de vie, rapport
  Fixtures/         # jeux de données rejouables (clients, produits, lots, TVA)
  Database/         # transaction, requêtes, vérifications
  Http/             # client HTTP, sessions, formulaires
  Guards/           # env recette, mails, synchro, PSP, cron
  Manifest/         # lecture YAML, sélection de suites
  Report/           # TAP, JUnit, Markdown, go/no-go
tests/              # tests de l'outil
examples/           # manifestes d'exemple
```

## Taxonomie des tests

| Niveau | Contenu | Emplacement |
|---|---|---|
| Unitaire | règles pures, sans base | `test/unit/` du module |
| Fonctionnel | classes Dolibarr + base, transaction annulée | `test/functional/` |
| Intégration | parcours complets, plusieurs modules | `test/mmi/` du fork |
| HTTP/UI | pages, formulaires, tunnel | suite du manifeste |
| Post-déploiement | hooks en base, menus, URLs, droits, gitlinks | suite `postdeploy` |

## Règles d'isolation

- refus de démarrer si l'environnement n'est pas explicitement marqué **recette** ;
- `MAIN_DISABLE_ALL_MAILS`, synchro PrestaShop et PSP désactivés par défaut ;
- transaction annulée ou base dédiée ; jamais d'écriture vers un système de production ;
- fixtures **rejouables** (la base de dev peut être resynchronisée depuis la prod) ;
- aucune donnée client réelle dans les rapports ;
- nettoyage garanti même en cas d'échec.

## Manifeste (brouillon)

```yaml
site: calicote-dolibarr
env:
  name: recette
  url: https://erp-test.example
  database: dolibarr_recette
suites:
  - id: chaine_vente
    path: test/mmi/chaine_vente_test.php
    level: integration
  - id: caisse
    path: htdocs/custom/mmiproductdluo/test/caisse_autovalide.php
    level: functional
checks:
  postdeploy:
    - hooks_registered
    - menus_reachable
    - module_options
    - gitlinks
```

Le runner accepte `--env`, `--full`, `--subset`, `--ticket`, `--report`, et retourne un
code de sortie exploitable en CI/MEP.

## Conventions pour les modules

- un test = un fichier autonome, exécutable en CLI, code de sortie 0/1 ;
- pas de dépendance à un état manuel de la base ;
- pas de `die`/`exit` hors du runner ;
- les tests qui écrivent en base refusent de tourner hors recette ;
- le rapport indique le nombre de cas, les échecs et la raison.

## Version-awareness

Le testkit doit tourner sur plusieurs versions du **cœur**, du **fork** et des
**modules**. Un test ne doit pas être écrit pour une seule version.

- **Sources de vérité** : `DOL_VERSION`, descripteur du module (`$this->version`,
  `$this->depends`), branche/tag git, et le bloc `compat` du manifeste de
  fonctionnalités.
- **Matrice** : `version cœur × version module × scénario d'installation`.
- **Déclaration** : chaque suite déclare `requires: {dolibarr: ">=19.0 <21.0",
  modules: {mmiproduct: ">=1.1"}}`. Le runner exécute, ou **skippe avec une raison
  explicite** — jamais de succès silencieux.
- **Adapters** : `CoreAdapter` par plage de versions (signatures, propriétés
  renommées, tables déplacées) et `ModuleAdapter` si un module change son API. Les
  tests n’embarquent pas de `if (version >= …)`.
- **Scénarios** : installation **vide + un module**, installation **composée** (jeu
  de modules), ou snapshot existant. Le runner résout les dépendances (descripteurs
  + `compat`) et installe dans l’ordre.
- **Rapport** : matrice versions × modules × tests, avec exécutés / skippés / échoués.

## Manifeste de fonctionnalités

Chaque module porte un inventaire **machine-readable** qui sert à la fois de
documentation, de base aux tests et de source pour `AGENTS.md`.

Trois artefacts, sans duplication :

| Fichier | Rôle | Public |
|---|---|---|
| `README.md` | présentation, installation, usage | utilisateur |
| `docs/features.yml` | **source de vérité** des fonctionnalités | outil + humain |
| `docs/technical.md` | doc technique (carte fonctionnalité → code) | développeur |
| `AGENTS.md` | point d’entrée court pour les agents | agent |

Les templates canoniques sont livrés par le module `mmidevtools`
(`gitlab:dolibarr/dolibarr-module-mmidevtools`), qui complète ModuleBuilder sans
modifier le cœur. Les fichiers de `docs/templates/` de ce dépôt sont des **exemples**
pour le testkit ; en cas d’écart, `mmidevtools` fait foi.

`docs/technical.md` et la partie « fonctionnalités » du README sont **générées** depuis
`features.yml` + le descripteur par `gen-module-docs.py`. `AGENTS.md` reste court et
renvoie vers ces fichiers ; il ne recopie pas la documentation.

### Schéma `features.yml`

- `schema` : version du schéma.
- `module`, `version`, `compat` (`dolibarr`, `modules`).
- `features[]` :
  - `id` stable (`mmidocuments.vat_notice`), `title` fr/en, `summary` fr/en ;
  - `status` (`stable`, `beta`, `experimental`, `deprecated`), `since`, `until` ;
  - `entrypoints[]` : `page`, `ajax`, `api`, `cli`, `cron`, `hook`, `trigger`,
    `class`, `template` — avec chemin, classe/méthode ou contexte ;
  - `data` : tables, colonnes, extrafields ;
  - `config` : constantes lues/écrites ;
  - `permissions` : droits requis ;
  - `deps` : modules et features requis ;
  - `tests[]` : suites/cas qui couvrent la fonctionnalité ;
  - `docs` : ancre README ou section technique.

### Règles de cohérence

- toute fonctionnalité déclarée a **au moins un entrypoint** et **au moins un test**
  (ou une justification explicite) ;
- tout hook, menu, route, trigger, constante ou table déclaré est rattaché à une
  fonctionnalité, ou marqué `infra` ;
- les IDs de fonctionnalités sont **stables** entre versions ; un retrait passe par
  `until` + `status: deprecated`, jamais par suppression silencieuse ;
- le schéma est validé en CI ; un module sans `features.yml` est signalé.

### Exemple

```yaml
schema: 1
module: mmidocuments
version: 1.2.0
compat:
  dolibarr: ">=19.0 <22.0"
  modules: {mmicommon: ">=1.0"}
features:
  - id: mmidocuments.vat_notice
    title:
      fr: Mentions d'exonération de TVA sur les PDF
      en: VAT exemption notices on PDFs
    status: stable
    since: 1.0
    entrypoints:
      - {type: hook, context: pdfgeneration, class: ActionsMMIDocuments, method: VATNotificationOnPDF}
    data: {tables: [], extrafields: [societe.tva_intra, socpeople.tva_intra]}
    config: [MMIDOCUMENTS_VAT_NOTIF_PDF_DISPLAY]
    tests: [custom/mmidocuments/test/phpunit/MMIDocumentsFunctionalTest.php]
    docs: README.md#mentions-de-tva-et-exonérations
```

### `AGENTS.md`

Contenu cible, volontairement court :

```markdown
# AGENTS.md — MMI<Module>

## But
## Commandes (lint, tests, upgrade)
## Carte du code (dossiers, classes, hooks)
## Fonctionnalités
- source : docs/features.yml
- doc technique : docs/technical.md
## Versions supportées
## Guardrails (pas de prod, mails/sync/PSP, secrets)
## Où changer quoi
```

## Feuille de route

1. **Squelette** : README, ADR, licence, structure, schéma `features.yml`, templates
   `technical.md` et `AGENTS.md`.
2. **Runner minimal version-aware** : bootstrap, `TestCase`, `requires`, rapport TAP,
   `--subset`.
3. **Pilote manifeste** : `features.yml` sur `mmicommon`, `mmiproduct` et
   `mmidocuments`, génération README/technical/AGENTS, validation CI.
4. **Reprise de `chaine_vente_test.php`** : fixture rejouable + qualifier l'échec
   `Facture::createFromOrder()`.
5. **Suites caisse et expédition/picking** : réutiliser les suites existantes.
6. **Scénarios composés** : installations vide/composée, détection de collisions
   (hooks, constantes, tables, menus, droits, routes, assets).
7. **Contrôles post-déploiement** et **go/no-go**.
8. **Manifeste Calicote** et branchement MEP.
9. **Façade `mmidevtools`** (optionnelle, recette uniquement).

## Questions ouvertes

- PHPUnit comme socle, ou runner maison conservé pour les tests CLI existants ?
- Base dédiée par run ou transaction annulée systématique ?
- Emplacement des fixtures partagées : dans le testkit ou dans chaque module ?
- Périmètre exact de la façade `mmidevtools` (aucun écran, ou écran de lancement/rapport) ?
- `features.yml` : versionné dans chaque module, ou centralisé dans le testkit avec
  un fichier par module ? (recommandation : dans le module, pour rester au plus près
  du code.)
- `technical.md` : généré à 100 %, ou rédigé puis complété par le générateur ?
