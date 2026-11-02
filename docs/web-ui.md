# Interface web du TestKit

## Stack

- PHP 7.4+ / 8.x, **sans framework**.
- Front controller `public/index.php`, routeur maison, contrôleurs, services, modèles, vues PHP.
- YAML pour les manifestes, JSON/JSONL pour les rapports et l’historique.
- Aucune dépendance runtime obligatoire (extension `yaml` ou fallback Symfony).

## Architecture

```text
public/index.php          # front controller + routes + auth
src/Http/                 # Request, Response, Router, Session, Auth, Csrf
src/Controller/           # Dashboard, Report, Run, Anomaly, Login
src/Service/              # ReportService, RunService, JobStore
src/Model/RunJob.php      # modèle de job
src/View/                 # rendu de vues + helpers
views/                    # layout, dashboard, reports, report, run, anomalies, login
config/app.php            # chemins, report_dir, job_dir
plugins/dolibarr/config/envs.php           # mapping env → htdocs/PHP (copier envs.example.php)
public/assets/style.css
```

Le CLI `core/bin/mmi-test` reste la source d’exécution ; l’interface web lit l’historique
et peut lancer un run en arrière-plan (`RunService`).

## Démarrage

```bash
cp plugins/dolibarr/config/envs.example.php plugins/dolibarr/config/envs.php   # adapter les chemins
cp .env.example .env                         # définir MMI_TESTKIT_WEB_PASSWORD
scripts/serve-web.sh 8099
# http://127.0.0.1:8099
```

Ou Apache : docroot `public/`, `AllowOverride All` pour `.htaccess`, et variables
`MMI_TESTKIT_WEB_PASSWORD` / `MMI_TESTKIT_REPORT_DIR` via `SetEnv` (ne pas committer le
mot de passe).

## Déploiement actuel (dev)

- URL : `https://testkit.iprospective.fr`
- DNS : CNAME `testkit.iprospective.fr → dev.iprospective.net.` dans la zone
  `iprospective.fr` de BIND sur le conteneur `core` (série `2026110101`).
- Vhost : `/etc/apache2/sites-available/dolibarr-testkit.conf` sur `dev`, docroot
  `/home/siteadm/iprospective/public/dolibarr-test/toolkit/core/public`.
- PHP-FPM : pool `dolibarr-testkit-81`, socket `/run/php/dolibarr-testkit-81.sock`.
- Secrets : mot de passe web dans `/root/.dolibarr-testkit-webpass` (600) sur `dev`,
  injecté via `env[MMI_TESTKIT_WEB_PASSWORD]` du pool ; `report_dir`/`job_dir` via
  `env[...]`.
- Certificat : `/etc/letsencrypt/live/testkit.iprospective.fr/` (HTTP-01,
  docroot ACME sur le vhost 80) ; renouvellement dry-run validé.

## Sécurité

- Authentification par mot de passe (env) ou hash bcrypt.
- CSRF sur les POST, sessions HttpOnly/SameSite.
- Échappement HTML systématique (`e()`).
- Lancer un run : manifeste limité à `plugins/dolibarr/manifests/`, racine limitée aux envs configurés,
  sous-ensemble limité aux suites du manifeste.
- Logs de jobs en `text/plain`.

## Limites

- Lancement par `nohup` (pas de file de jobs persistante).
- Pas de live streaming (polling du log).
- Pas de RBAC, pas d’OIDC.
- Pas de comparaison visuelle entre runs.
- Pas de gestion d’artefacts (PDF, captures).

## Idées d’amélioration

1. **File de jobs + worker** : remplacer `nohup` par une table/file, worker CLI, états
   `queued/running/done/failed`, reprise après redémarrage.
2. **Live logs** : SSE ou WebSocket pour suivre les contrôles en temps réel.
3. **Comparaison de runs** : diff entre commits/PHP, régressions, premiers commits en
   échec, lien vers `git bisect`.
4. **Manifestes de fonctionnalités** : importer `docs/features.yml` des modules,
   matrice de couverture, tests manquants.
5. **Scénarios composés** : installation vide + N modules, détection de collisions
   (hooks, constantes, tables, menus, droits).
6. **Benchmark** : dashboard des volumes, temps, mémoire, requêtes SQL, courbes.
7. **Authentification** : OIDC/GitLab, RBAC (lecture/lancement/admin), audit log.
8. **Artefacts** : téléchargement JSON/Markdown/PDF, captures, logs packagés.
9. **Baselines** : marquer un run comme référence, seuils de régression, alertes.
10. **CI/MEP** : webhook GitLab, gate MR, commentaire automatique du verdict.
11. **Anomalies actionnables** : ack, assignation, création de ticket Redmine,
    lien commit/MR.
12. **API JSON** : endpoints `/api/runs`, `/api/envs`, `/api/anomalies` pour
    intégrations externes.
13. **Mode « dry-run »** : prévisualiser les suites/contrôles sans écrire en base.
14. **Rétention** : purge/archivage des rapports, export S3/Nextcloud.
15. **Thème impression** : rapport propre pour le DevCamp/revue client.
