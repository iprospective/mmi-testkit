# MMI Dolibarr TestKit

Outillage de **tests et de non-régression** pour le fork Dolibarr MMI et ses modules.

> **Statut : campagnes S1–S5 opérationnelles** sur 19.0-mmi et 20.0-mmi-merge.
> Le périmètre et l'architecture sont posés dans
> [`docs/architecture.md`](docs/architecture.md), le plan de couverture initial
> (Calicote, Calyclay, Pisceen, MMI) dans
> [`docs/plan-coverage.md`](docs/plan-coverage.md), et les résultats dans
> [`docs/run-2026-11-01.md`](docs/run-2026-11-01.md).

## Ce que ce dépôt n'est pas

- **Pas un module Dolibarr.**
- **Pas du code destiné à la production.**
- **Pas une dépendance de `mmicommon`** ni d'un module métier.

L'outil s'installe et s'exécute uniquement sur un environnement de **recette** ou de
développement, avec une base dédiée ou des transactions annulées.

## Objectif

Fournir un socle commun pour :

1. tester le **cœur** du fork Dolibarr et les parcours transverses
   (chaîne de vente, caisse, expédition, synchronisation) ;
2. fournir des **outils** aux modules MMI pour écrire leurs propres tests
   (unitaire, fonctionnel, intégration) ;
3. rendre un **verdict go/no-go** avant mise en production, avec un manifeste
   rejouable et des contrôles post-déploiement ;
4. isoler les tests des intégrations externes (mails, SMS, PSP, PrestaShop).

## Principes

- **Tests au plus près du code** : `test/mmi/` dans le fork, `test/` dans chaque module.
- **Outil indépendant** : bootstrap Dolibarr, bibliothèque de test, runner CLI.
- **Manifeste + runner** : `--env`, `--full`, `--subset`, `--ticket`, sortie TAP/JUnit/Markdown.
- **Recette uniquement** : refus de démarrer sans garde-fou d'environnement.
- **Fixtures rejouables** : pas de données posées à la main.
- **Isolation** : transaction annulée, mails/synchro/PSP coupés, aucune écriture vers
  un système de production.

## Démarrage rapide

```bash
composer install   # fallback YAML si l'extension php-yaml n'est pas disponible
php bin/mmi-test --manifest=manifests/19.0-mmi.yml \
  --dolibarr-root=/chemin/vers/htdocs --report=/tmp/report.json
```

Options : `--subset`, `--list`, `--history`, `--diagnose`, `--report-dir`,
`--verbose`, `--keep` (debug). Le runner refuse de démarrer si `MMI_TEST_ENV` n'est
pas présent. Voir [`docs/reporting.md`](docs/reporting.md) pour l'historique et le
diagnostic.

## Structure

```text
bin/mmi-test                 # point d'entrée CLI
src/                         # bootstrap, TestCase, fixtures, guards, manifest, runner
workflows/                   # parcours métier (S1 sales_chain, …)
manifests/                   # 19.0-mmi.yml, 20.0-mmi.yml
fixtures/                    # à venir : fixtures partagées et benchmarks
docs/architecture.md         # décision et conventions
docs/plan-coverage.md        # couverture cible
docs/run-2026-11-01.md       # résultats de campagne
docs/anomalies.md           # anomalies et points ouverts
docs/php-matrix.md          # matrice PHP
docs/web-ui.md             # interface web
public/                    # front controller + assets
views/                     # vues
```

## Interface web

```bash
scripts/serve-web.sh 8099
# http://127.0.0.1:8099
```

Voir [`docs/web-ui.md`](docs/web-ui.md) (architecture, routes, sécurité, idées
d’amélioration).

## Suivi

- Ticket de suivi : **RM3515** (Calicote), requalifié vers cet outil.
- Cas couverts : chaîne de vente, documents/PDF, produits/stock/lot, expédition et
  paiements. Suivants : synchro boutique, sécurité, benchmark.

## Licence

GNU GPL v3 ou ultérieure — voir `LICENSE`.
