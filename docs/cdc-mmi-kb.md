# CDC — `mmi-kb`

## Base de connaissance transverse des outils maintenus

**Statut** : conception validée, implémentation à venir  
**Nom** : `mmi-kb` (CLI et outil) — dépôt `iprospective/mmi-kb`  
**Ancien nom de travail** : `mmi-dev-knowledge`  
**Projet Redmine** : `test-kit`  
**Client** : `iprospective`

---

## 1. Objet

`mmi-kb` centralise les **connaissances génériques** sur les logiciels, plateformes et outils
maintenus ou utilisés : Dolibarr, PrestaShop, Nextcloud, Roundcube, Karl PM, GitLab, Redmine,
Zabbix, etc.

Il fournit :

- un **modèle de connaissance** stable et versionné ;
- des **vues génériques** par famille d’usage ;
- une **CLI** et une **API read-only** ;
- des **sources** et collecteurs pour suivre les versions, modules, hooks, triggers, dépôts,
  wikis et forums officiels.

Il est **indépendant de PM** et consommé par PM, `mmi-testkit`, Karl PM et tout autre outil.

## 2. Principe central

> **`mmi-kb` contient les faits génériques sur les produits/outils.  
> PM contient la mémoire d’un projet client.  
> Les tâches référencent `mmi-kb` ; `mmi-kb` ne référence ni client ni tâche.**

Corollaires :

- les docs qui **définissent Dolibarr** (hooks, triggers, modules, menus, objets, versions,
  licence, communauté) vont dans `mmi-kb` ;
- les docs d’un **CDC client**, d’une évolution, d’un environnement ou d’une instance vont
  dans PM (docs projet + wiki Redmine) ;
- une correction spécifique à un client (libellé DDM, hook désactivé dans un template) reste
  dans PM ;
- un cas client qui révèle un **comportement générique** peut être abstrait puis promu dans
  `mmi-kb` (quirk), sans référence client/ticket ;
- aucun secret, aucune URL d’instance, aucun identifiant client dans `mmi-kb`.

## 3. Périmètre

### Inclus

- fiches **outil/plateforme** : rôle, éditeur, licence, dépôts, wiki/forum/chat, support,
  politique de release et de sécurité, langues, communauté ;
- fiches **version** : dates, EOL, environnements supportés (PHP/DB/navigateur), breaking
  changes, chemins de migration ;
- fiches **composant** : modules, plugins, hooks, triggers, templates, menus, objets,
  dépendances, mainteneur, fork/upstream ;
- **compatibilités** outil/version/composant/PHP/OS ;
- **quirks** : symptôme, cause, contournement, correctif, versions affectées, preuve externe ;
- **recettes de test génériques** (famille `tests`) ;
- **sources** : où trouver les dernières versions, modules, docs officielles, forums, dépôts ;
- **licences** et obligations ;
- **patterns** génériques par produit (hooks, triggers, conventions de templates, structures
  d’objets).

### Exclu

- CDC, décisions, réunions, comptes-rendus client ;
- tâches et tickets (PM/Redmine) ;
- environnements, URLs, credentials ;
- correctifs et configurations spécifiques à un client ;
- rapports de test et historiques d’exécution (restent dans `mmi-testkit`) ;
- secrets de toute nature.

## 4. Modèle de connaissance

### Kinds initiaux

| Kind | Rôle |
|---|---|
| `tool` | produit/plateforme et métadonnées générales |
| `component` | module/plugin et surface technique |
| `version` | version, EOL, compatibilités, migrations |
| `compatibility` | matrice outil/version/composant/PHP/OS |
| `quirk` | comportement inattendu et remédiation |
| `recipe` | recette de test générique |
| `source` | source officielle à surveiller |
| `license` | licence et obligations |
| `vendor` | éditeur/communauté/support |

Les **vues** ne sont pas des kinds.

### Identifiants

Stables, lisibles, jamais réutilisés :

```text
tool:dolibarr
tool:dolibarr@20.0
component:dolibarr/mmiproductdluo
quirk:dolibarr/mmiproductdluo/create-from-order-extrafields
recipe:dolibarr/sales-chain
source:dolibarr/official-wiki
license:dolibarr/gpl-3.0
```

### Champs transverses

- `summary` fr/en ;
- `applies_to` : plages de versions outil/module/PHP ;
- `status` : `draft`, `verified`, `deprecated` ;
- `confidence` : `low`, `medium`, `high` ;
- `provenance` : `source` **externe** (URL officielle, commit upstream, release), `fetched_at`,
  `verified_at`, `verified_by` — **pas de RM/client** ;
- `relations` : `depends_on`, `conflicts_with`, `fixed_in`, `supersedes`, `documents`,
  `tested_by` ;
- `refs` : URLs officielles, docs upstream, commits upstream ;
- `x-*` pour l’extensibilité.

## 5. Arborescence

```text
mmi-kb/
  schema/                       # JSON Schema des kinds
  tools/
    dolibarr/
      tool.yml
      sources.yml
      versions/20.0.yml
      components/
        mmiproduct/
          component.yml
          hooks.yml
          triggers.yml
          objects.yml
          patterns.yml
          docs/*.md
    prestashop/
    nextcloud/
    roundcube/
    karl-pm/
    gitlab/
    redmine/
    zabbix/
  quirks/
    dolibarr/mmiproductdluo/create-from-order-extrafields.yml
  recipes/
    dolibarr/sales-chain.yml
  vendors/
  licenses/
  collectors/                   # optionnel : collecte par plateforme
    dolibarr/
    prestashop/
  bin/mmi-kb
  api/                          # read-only, plus tard
  index/                        # SQLite généré, non versionné
```

Pas de dossier `projects/`, `clients/`, `redmine/`, `testkit/`.

## 6. Vues génériques

Les vues sont des **familles d’usage**, pas des outils.

| Famille | Contenu | Consommateur nommé |
|---|---|---|
| `docs` | documentation/wiki générique | `pm-wiki-sync`, générateur Markdown/HTML |
| `tests` | recettes, contrôles, fixtures | `mmi-testkit` |
| `agents` | contexte compact pour agents | `karl-agent`, `pm-context` |
| `compat` | matrices de compatibilité | `mmi-testkit`, PM |
| `catalog` | modules, hooks, triggers, objets, menus | PM cockpit, doc |
| `releases` | versions, EOL, releases | PM, Karl |
| `licenses` | licences et obligations | PM |
| `sources` | wiki/forum/dépôts/API officiels | collecteurs, PM |

CLI :

```bash
mmi-kb view docs   --tool dolibarr --version 20.0 --format markdown
mmi-kb view tests  --tool dolibarr --format json
mmi-kb view agents --tool dolibarr --version 20.0 --format json
mmi-kb view compat --tool dolibarr --php 8.4
```

API read-only :

```text
GET /views/docs?tool=dolibarr&version=20.0
GET /views/tests?tool=dolibarr
GET /views/agents?tool=dolibarr&version=20.0
GET /views/compat?tool=dolibarr&php=8.4
```

Aucun endpoint `/redmine` ou `/testkit` dans `mmi-kb`.

## 7. CLI

```bash
mmi-kb validate
mmi-kb index
mmi-kb get tool:dolibarr
mmi-kb resolve dolibarr@20.0 --component mmiproduct
mmi-kb quirks --tool dolibarr --version 20.0 --component mmiproduct
mmi-kb compat --tool dolibarr --php 8.4
mmi-kb recipes --tool dolibarr --component mmiworkflow
mmi-kb view agents --tool dolibarr --version 20.0
mmi-kb stale --older-than 90d
mmi-kb export --format json|markdown
mmi-kb serve --port 8091
```

## 8. Collecte et fraîcheur

`sources.yml` décrit les sources officielles et la stratégie de rafraîchissement :

```yaml
official_wiki: ...
official_forum: ...
repositories: [...]
releases: ...
modules: ...
refresh: weekly
```

Les collecteurs (`collectors/dolibarr`, `collectors/prestashop`, etc.) peuvent proposer :

- dernières versions/releases ;
- catalogue des modules et métadonnées ;
- hooks/triggers exposés par version ;
- templates et objets.

Une donnée collectée est **`draft`** jusqu’à revue. On stocke `fetched_at`, `source`,
`verified_at`, `verified_by`.

## 9. Gouvernance

- **Propriétaire** par outil/composant (`CODEOWNERS`), revue obligatoire.
- `draft → verified → deprecated` ; `supersedes` pour les remplacements.
- Preuve externe obligatoire pour `verified`.
- Validation CI : schéma, IDs uniques, plages de versions, liens, conflits de compatibilité.
- `stale` : alerte de fraîcheur, pas blocage silencieux.
- Détection de conflits : quirks contradictoires, compatibilités incompatibles, versions qui
  se chevauchent.
- **Pas de secrets, pas de client, pas de ticket** dans le dépôt.
- Licence du code GPL/Apache ; contenu sous CC BY-SA 4.0 (à confirmer).

## 10. Relation avec PM

PM est la **mémoire projet** et le **workflow**. `mmi-kb` est le **savoir produit**.

- Docs produit → `mmi-kb`.
- CDC/évolution/instance client → PM + wiki.
- Une tâche PM peut porter :
  ```yaml
  knowledge:
    action: update
    refs: [tool:dolibarr, component:dolibarr/mmiproduct, doc:dolibarr/hooks]
    target: tools/dolibarr/components/mmiproduct/hooks.yml
  ```
- La tâche exige une MR `mmi-kb`, vérifie le caractère générique et enregistre le commit
  knowledge dans son journal.
- `mmi-kb` ne référence jamais la tâche, le client ou le ticket.
- Un cas client peut être promu en quirk générique après abstraction, sans référence client.

### Double synchronisation PM ↔ Redmine

La synchronisation est **bidirectionnelle** :

- PM est la vue la plus complète et la **référence de travail** ;
- Redmine peut créer des tâches, de l’historique et des documents wiki ;
- PM importe et réconcilie ; les conflits sont tracés et arbitrés côté PM ;
- `mmi-kb` n’intervient pas dans cette synchronisation.

## 11. Relation avec `mmi-testkit`

- Le manifeste déclare `tool`, `version`, `components`, éventuellement `knowledge`.
- L’adaptateur `mmi-testkit` interroge `mmi-kb` (CLI ou API) pour les recettes, quirks,
  compatibilités et valeurs attendues.
- Le rapport contient `knowledge_commit` et les IDs utilisés.
- `mmi-testkit` ne stocke pas de connaissance produit.

## 12. Relation avec Karl PM

Karl consomme la vue `agents` :

- paquet de contexte compact (faits, versions, quirks, liens) ;
- budget de tokens ;
- filtrage outil/version ;
- complété par les docs PM du projet client ;
- jamais de secrets ni de données client dans `mmi-kb`.

## 13. Migration

1. Créer `mmi-kb` (schéma, CLI, index) sous `iprospective`, Redmine `test-kit`.
2. Migrer `knowledge/dolibarr/*`, `knowledge/prestashop/*`, `knowledge/redmine/*`,
   `knowledge/gitlab/*`, `knowledge/zabbix/*`, etc. vers `tools/*/docs` et `sources`.
3. Extraire les quirks génériques de `docs/anomalies.md` et des CDC, sans références client.
4. Laisser dans PM : CDC, décisions, réunions, environnements, credentials, correctifs
   spécifiques.
5. Ajouter `knowledge_refs` / `knowledge.lock` dans les docs projet.
6. Brancher PM (`pm-kb`), TestKit (`mmi-testkit`), Karl (`agents`).
7. API read-only puis MCP.

## 14. Roadmap

| Jalon | Contenu |
|---|---|
| K1 | Schéma v0 + CLI `validate/index/get` + dépôt |
| K2 | Fiches `tool`, `version`, `component`, `source` Dolibarr |
| K3 | Quirks + recipes Dolibarr, migration des anomalies |
| K4 | Vues `docs`, `tests`, `agents`, `compat` |
| K5 | Adaptateurs PM, TestKit, Karl |
| K6 | Sources/collecteurs versions/modules/hooks |
| K7 | API read-only, MCP, CI |

## 15. Questions ouvertes

- Licence du contenu (CC BY-SA 4.0 ou interne).
- API en Python (aligné PM/Karl) ou PHP (aligné TestKit) ?
- Niveau de collecte automatique accepté par source.
- Politique de rétention/archivage.
- Faut-il un mode « write API » plus tard, avec revue/PR, ou rester Git-only ?
