# Compte-rendus, historique et diagnostic

## Rapport de run

Chaque exécution produit un JSON :

```json
{
  "schema": 1,
  "run_id": "20261101-005912-d75f81e-3094",
  "date": "2026-11-01T00:59:12+01:00",
  "env": "dolibarr-test-19",
  "site": "dolibarr-test-19",
  "php": "7.4.x",
  "dolibarr_version": "19.0.4",
  "code": {"branch": "19.0-mmi", "commit": "d75f81e...", "dirty": false},
  "suites": [
    {
      "suite": "sales_chain",
      "ok": true,
      "checks_count": 61,
      "passed": 61,
      "failed": 0,
      "failures": [],
      "warnings": [],
      "checks": [
        {"label": "le devis est créé", "ok": true, "got": true, "want": true}
      ]
    }
  ],
  "summary": {"checks": 61, "passed": 61, "failed": 0, "warnings": 1, "ok": true}
}
```

`code` vient de `<env>/.mmi-test-env.json` (ou de git si le checkout est présent).
Le fichier contient `branch`, `commit`, `built_at`, `db`, `php`, `source`.

## Stockage

Avec `--report-dir=DIR` (défaut `reports/`) :

```
DIR/
  <env>/
    <run_id>.json        # rapport complet
    latest.json          # dernier rapport
    history.jsonl        # une ligne JSON par run
```

## Commandes

```bash
# exécution + historisation
mmi-test --manifest=manifests/19.0-mmi.yml \
         --dolibarr-root=/.../htdocs \
         --report-dir=/.../reports

# lister les runs
mmi-test --manifest=manifests/19.0-mmi.yml --history --limit=20

# diagnostic du dernier run
mmi-test --manifest=manifests/19.0-mmi.yml --diagnose
```

## Diagnostic

`--diagnose` affiche :

- le dernier run (date, commit, branche, contrôles, échecs, avertissements) ;
- chaque échec avec `got`/`want` ;
- le **premier commit en échec** pour ce contrôle ;
- le **dernier commit en succès** (baseline) ;
- un indice issu de `known-issues.yml` quand il existe.

Exemple de sortie :

```
[sales_chain] createFromOrder : got='retour=-1' want='ok'
  premier échec : d75f81e (2026-11-01)
  dernier succès : aucun
  diagnostic : Facture::createFromOrder() retourne -1 après création partielle...
```

## Known issues

`known-issues.yml` associe un indice à une suite ou à un contrôle :

```yaml
sales_chain:
  createFromOrder:
    hint: "Facture::createFromOrder retourne -1 après création partielle..."
```

Ce n'est pas un masquage : le contrôle reste en échec ou en avertissement dans le
rapport ; seul le diagnostic est enrichi.

## Limites

- L'historique est local au répertoire `--report-dir` ; il n'est pas commité.
- Un run doit être **comparable** : même manifeste, même branche, même base, même PHP.
  Les métadonnées sont enregistrées pour le vérifier.
- Les tests qui écrivent en base ou génèrent des fichiers doivent nettoyer derrière eux.
