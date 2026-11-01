# Matrice PHP — campagne 2026-11-01

## Commande

```bash
cd /home/siteadm/iprospective/public/dolibarr-test/toolkit
sudo -u php_iprospective bash scripts/php-matrix.sh \
  manifests/20.0-mmi.yml \
  /home/siteadm/iprospective/public/dolibarr-test/20.0-mmi/htdocs \
  /home/siteadm/iprospective/public/dolibarr-test/reports \
  sales_chain
```

Remplacer le manifeste et le `htdocs` pour l’env 19. Le script parcourt 7.4, 8.1,
8.2, 8.3 et 8.4, et écrit chaque run dans l’historique.

## Résultats S1

| Cœur | PHP 7.4 | PHP 8.1 | PHP 8.2 | PHP 8.3 | PHP 8.4 |
|---|---|---|---|---|---|
| 19.0-mmi | OK | **KO** (`pdf_sponge:849`) | **KO** | **KO** | **KO** |
| 20.0-mmi-merge | OK | OK | OK | OK | OK |

Le KO 19.0-mmi est le `round()` de `pdf_sponge` sur une chaîne (A2). Le 20.0.5
passe avec le correctif `(float)` appliqué dans l’env de test ; sans ce correctif,
il échoue aussi sur 8.1+.

## Version recommandée

- **20.0-mmi-merge** : 8.3 ou 8.4 (après report du correctif `pdf_sponge` dans le fork).
- **19.0-mmi** : 7.4 tant que A2 n’est pas corrigé.

## Prérequis

- Extension `yaml` pour chaque PHP (7.4, 8.1, 8.4 déjà présents ; 8.2/8.3 installés
  pour la campagne).
- Mêmes bases/DB que les envs ; `MMI_TEST_ENV=1`.
