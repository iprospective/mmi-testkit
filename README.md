# MMI TestKit

Socle générique de **tests et de non-régression** pour applications web et ERP :
Dolibarr, PrestaShop, Nextcloud, WordPress, etc.

> Dépôt public : <https://github.com/iprospective/mmi-testkit>  
> Licence : GPL-3.0-or-later.

## Écosystème

MMI TestKit est utilisé **conjointement avec Karl/PM** — le même outil,
[`karl-ai-project-manager`](https://github.com/iprospective/karl-ai-project-manager).
Karl/PM est le **gestionnaire de projet assisté par IA, modulaire** : il porte le
projet, les tâches, les décisions, les environnements et les workflows. TestKit
exécute les suites et produit le verdict ; Karl/PM lit les rapports. TestKit ne
remplace pas Karl/PM.

> **État** : le moteur et le **premier plugin Dolibarr** sont opérationnels.
> Le split `core/` + `plugins/dolibarr` est effectué. Les plugins PrestaShop,
> Nextcloud et WordPress sont planifiés, pas encore livrés.
>
> CDC : [test-kit](https://tasks.iprospective.fr/projects/test-kit/wiki/Cdc-test-kit)
> et [knowledge-base](https://tasks.iprospective.fr/projects/knowledge-base/wiki/Cdc-mmi-dev-knowledge).

## Objectif

Fournir un socle commun pour :

1. tester une application et ses parcours transverses ;
2. fournir des **plugins** par outil, sans modifier le moteur ;
3. rendre un **verdict go/no-go** avant mise en production, avec un manifeste
   rejouable et des contrôles post-déploiement ;
4. isoler les tests des intégrations externes (mails, SMS, PSP, boutique, SSO).

Le moteur ne doit connaître **aucune** application métier. Un plugin porte le
bootstrap, les fixtures, les workflows, les manifests et la configuration d'env.

## Applications

| Application | État |
|---|---|
| Dolibarr | premier plugin, S1–S7 opérationnels |
| PrestaShop | planifié |
| Nextcloud | planifié |
| WordPress | planifié |
| Autres | via un nouveau plugin |

## Principes

- **Tests au plus près du code** : `test/` dans l'application ou le module testé.
- **Moteur indépendant** : runner, assertions, manifests, historique, rapports.
- **Plugin par outil** : bootstrap, fixtures, workflows et manifests spécifiques.
- **Manifeste + runner** : `--env`, `--full`, `--subset`, `--ticket`, sortie TAP/JUnit/Markdown.
- **Recette uniquement** : refus de démarrer sans garde-fou d'environnement.
- **Fixtures rejouables** : pas de données posées à la main.
- **Isolation** : transaction annulée, intégrations externes coupées, aucune écriture
  vers un système de production.

## Architecture cible

```text
core/                       # runner, TestCase, manifest, report, history, http, web
  bin/mmi-test
  src/
  public/  views/
  config/
plugins/
  dolibarr/                 # bootstrap, fixtures, workflows, manifests, envs
  prestashop/               # à venir
  nextcloud/                # à venir
  wordpress/                # à venir
docs/
```

Le split est effectué : le moteur ne contient plus de chemin Dolibarr.

## Démarrage — plugin Dolibarr

```bash
composer install   # fallback YAML si l'extension php-yaml n'est pas disponible
php core/bin/mmi-test --manifest=plugins/dolibarr/manifests/19.0-mmi.yml \
  --dolibarr-root=/chemin/vers/htdocs --report=/tmp/report.json
```

Options : `--subset`, `--list`, `--history`, `--diagnose`, `--report-dir`,
`--verbose`, `--keep` (debug). Le runner refuse de démarrer si `MMI_TEST_ENV` n'est
pas présent. Voir [`docs/reporting.md`](docs/reporting.md).

## Interface web

```bash
core/scripts/serve-web.sh 8099
# http://127.0.0.1:8099
```

Déploiement de recette : <https://testkit.iprospective.fr>.
Voir [`docs/web-ui.md`](docs/web-ui.md).

## Structure actuelle

```text
core/                        # moteur générique : CLI, API, UI, runner, manifests, reports
core/src/                    # TestCase, Runner, Manifest, Http, View, services
core/public/                 # front controller + assets
core/views/                  # vues
core/config/app.php          # configuration du moteur
plugins/dolibarr/
  src/                       # Bootstrap, Environment, Fixtures Dolibarr
  workflows/                 # parcours Dolibarr (S1 sales_chain, …)
  manifests/                 # manifests Dolibarr
  config/envs.php            # environnements Dolibarr
  scripts/php-matrix.sh      # matrice PHP Dolibarr
docs/                        # documentation projet
```

## Suivi

- TestKit : RM3586 (split core/plugins), RM3595 (déploiement).
- Plugin Dolibarr : RM3515.
- Prochains plugins : PrestaShop, Nextcloud, WordPress, après stabilisation du core.

## Licence

GNU GPL v3 ou ultérieure — voir `LICENSE`.
