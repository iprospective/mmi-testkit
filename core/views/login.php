<?php $title = $title ?? 'Connexion'; ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Connexion — MMI TestKit</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body class="login-page">
<main class="login-box">
  <h1>MMI TestKit</h1>
  <p class="opacity">Interface de recette — accès protégé.</p>
  <?php if (!empty($error)): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
  <?php if (empty($configured)): ?>
    <div class="flash error">Définir <code>MMI_TESTKIT_WEB_PASSWORD</code> (ou <code>MMI_TESTKIT_WEB_PASSWORD_HASH</code>) avant de se connecter.</div>
  <?php endif; ?>
  <form method="post" action="/login">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <label>Mot de passe
      <input type="password" name="password" autofocus autocomplete="current-password">
    </label>
    <button type="submit">Se connecter</button>
  </form>
</main>
</body>
</html>
