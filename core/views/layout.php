<?php
$success = \Mmi\TestKit\Http\Session::pullFlash('success');
$error = \Mmi\TestKit\Http\Session::pullFlash('error');
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'MMI TestKit') ?> — MMI TestKit</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<header class="topbar">
  <div class="brand"><a href="/">MMI TestKit</a></div>
  <nav>
    <a href="/">Tableau de bord</a>
    <a href="/reports">Rapports</a>
    <a href="/run">Lancer</a>
    <a href="/anomalies">Anomalies</a>
    <a href="/logout">Déconnexion</a>
  </nav>
</header>
<main>
  <?php if ($success): ?><div class="flash success"><?= e($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
  <?= $content ?>
</main>
<footer>MMI DevTools / TestKit — recette uniquement</footer>
</body>
</html>
