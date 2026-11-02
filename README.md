# MMI TestKit

Socle générique de **tests et de non-régression** pour applications web et ERP :
Dolibarr, PrestaShop, Nextcloud, WordPress, etc.

> Dépôt public : <https://github.com/iprospective/mmi-testkit>  
> Licence : GPL-3.0-or-later.

> **État** : le moteur et le **premier plugin Dolibarr** sont opérationnels.
> Le split `core/` + `plugins/` est en cours (RM3586). Les plugins PrestaShop,
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
plugins/
  dolibarr/                 # bootstrap, fixtures, workflows, manifests, envs
  prestashop/               # à venir
  nextcloud/                # à venir
  wordpress/                # à venir
bin/mmi-test
public/  views/
docs/
```

Le split est en cours. Tant qu'il n'est pas terminé, les éléments du plugin Dolibarr
vivent encore dans l'arborescence historique.

## Démarrage — plugin Dolibarr

```bash
composer install   # fallback YAML si l'extension php-yaml n'est pas disponible
php bin/mmi-test --manifest=manifests/19.0-mmi.yml \
  --dolibarr-root=/chemin/vers/htdocs --report=/tmp/report.json
```

Options : `--subset`, `--list`, `--history`, `--diagnose`, `--report-dir`,
`--verbose`, `--keep` (debug). Le runner refuse de démarrer si `MMI_TEST_ENV` n'est
pas présent. Voir [`docs/reporting.md`](docs/reporting.md).

## Interface web

```bash
scripts/serve-web.sh 8099
# http://127.0.0.1:8099
```

Déploiement de recette : <https://testkit.iprospective.fr>.
Voir [`docs/web-ui.md`](docs/web-ui.md).

## Structure actuelle

```text
bin/mmi-test                 # point d'entrée CLI
src/                         # moteur et classes du premier plugin
workflows/                   # workflows Dolibarr (S1 sales_chain, …)
manifests/                   # manifests Dolibarr
docs/architecture.md         # décision et conventions
docs/plan-coverage.md        # couverture Dolibarr
docs/anomalies.md            # anomalies et points ouverts
docs/php-matrix.md           # matrice PHP
docs/web-ui.md               # interface web
public/                      # front controller + assets
views/                       # vues
```

## Suivi

- TestKit : RM3586 (split core/plugins), RM3595 (déploiement).
- Plugin Dolibarr : RM3515.
- Prochains plugins : PrestaShop, Nextcloud, WordPress, après stabilisation du core.

## Licence

GNU GPL v3 ou ultérieure — voir `LICENSE`.
