# AGENTS.md — MMI<Module>

> Modèle à adapter. Rester court : ce fichier est un point d'entrée, pas la doc.

## But

Décrire en 2–4 phrases ce que fait le module et son rôle dans l'écosystème MMI.

## Commandes

```bash
# lint
php -l <fichier.php>
# tests unitaires / fonctionnels
php test/...
# upgrade du module (recette uniquement)
php htdocs/install/upgrade.php ...
```

## Carte du code

- `core/modules/modMMI<Module>.class.php` — descripteur (version, dépendances, droits,
  menus, hooks, triggers, SQL).
- `class/` — classes métier et actions/hooks.
- `admin/` — pages de configuration.
- `test/` — suites unitaires, fonctionnelles, fixtures.
- `docs/features.yml` — **source de vérité** des fonctionnalités.
- `docs/technical.md` — doc technique générée (fonctionnalité → code).

## Versions supportées

- Cœur Dolibarr : `>=19.0 <22.0` (à ajuster).
- Modules MMI : `mmicommon` obligatoire, activé en premier.
- Branche de release : `20.0` ; `dev` = version en cours + compatibilité `develop`.

## Guardrails

- Jamais de test ni d'écriture sur une base de production.
- Mails, synchro PrestaShop, PSP et cron désactivés par défaut en test.
- Ne pas committer de secrets ; les constantes sensibles sont chiffrées (`dolcrypt:`).
- Ne pas modifier un module tiers sans accord.

## Où changer quoi

| Besoin | Fichier / zone |
|---|---|
| Ajouter une fonctionnalité | descripteur + classe + `docs/features.yml` + test |
| Changer la configuration | `admin/setup.php` + `features.yml` (`config`) |
| Ajouter un hook | descripteur (`module_parts['hooks']`) + classe d'actions + test |
| Ajouter une table/colonne | `sql/` + migration + `features.yml` (`data`) |
| Documenter | `features.yml`, puis régénérer `README.md` / `docs/technical.md` |

## Références

- `docs/features.yml`
- `docs/technical.md`
- `README.md`
