# Architecture — MMI Dolibarr TestKit

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
4. **Façade optionnelle `mmitest`** : si un écran d'admin est souhaité, un module dédié
   *recette uniquement*, sans dépendance, appelle le runner. Il ne contient pas les tests.

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

## Feuille de route

1. **Squelette** : README, ADR, licence, structure.
2. **Runner minimal** : bootstrap, `TestCase`, rapport TAP, `--subset`.
3. **Reprise de `chaine_vente_test.php`** : fixture rejouable + qualifier l'échec
   `Facture::createFromOrder()`.
4. **Suites caisse et expédition/picking** : réutiliser les suites existantes.
5. **Contrôles post-déploiement** et **go/no-go**.
6. **Manifeste Calicote** et branchement MEP.
7. **Façade `mmitest`** (optionnelle, recette uniquement).

## Questions ouvertes

- PHPUnit comme socle, ou runner maison conservé pour les tests CLI existants ?
- Base dédiée par run ou transaction annulée systématique ?
- Emplacement des fixtures partagées : dans le testkit ou dans chaque module ?
- Périmètre exact de la façade `mmitest` (aucun écran, ou écran de lancement/rapport) ?
