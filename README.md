# MMI Dolibarr TestKit

Outillage de **tests et de non-régression** pour le fork Dolibarr MMI et ses modules.

> **Statut : squelette / conception.** Le périmètre et l'architecture sont posés dans
> [`docs/architecture.md`](docs/architecture.md), et le plan de couverture initial
> (Calicote, Calyclay, Pisceen, MMI) dans
> [`docs/plan-coverage.md`](docs/plan-coverage.md). Le runner et les premières suites
> restent à implémenter.

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

## Structure prévue

```text
bin/mmi-test                 # point d'entrée CLI
src/                         # bootstrap, TestCase, fixtures, guards, manifest, report
tests/                       # tests de l'outil lui-même
docs/architecture.md         # décision et conventions
examples/                    # manifestes et suites d'exemple
```

## Suivi

- Ticket de suivi : **RM3515** (Calicote), requalifié vers cet outil.
- Premier cas d'usage : chaîne de vente Dolibarr (devis → commande → facture → paiement),
  puis caisse, expédition/picking et synchro boutique.

## Licence

GNU GPL v3 ou ultérieure — voir `LICENSE`.
