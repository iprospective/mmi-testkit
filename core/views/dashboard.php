<section class="page-head">
  <h1>Tableau de bord</h1>
  <p class="opacity">Derniers runs par environnement et jobs récents.</p>
</section>

<section class="cards">
<?php foreach ($envs as $name => $env): ?>
  <?php $latest = $env['latest']; ?>
  <article class="card">
    <h2><?= e($env['label'] ?: $name) ?></h2>
    <p class="mono"><?= e($name) ?></p>
    <?php if ($latest): ?>
      <?php $ok = !empty($latest['summary']['ok']); ?>
      <p><span class="badge <?= $ok ? 'ok' : 'ko' ?>"><?= $ok ? 'OK' : 'ÉCHEC' ?></span>
        <?= e($latest['summary']['checks']) ?> contrôles,
        <?= e($latest['summary']['failed']) ?> échec(s),
        <?= e($latest['summary']['warnings']) ?> avertissement(s)
      </p>
      <p class="mono small"><?= e(substr($latest['code']['commit'], 0, 8)) ?> · <?= e($latest['code']['branch']) ?> · <?= e(substr($latest['date'], 0, 19)) ?></p>
      <p><a class="button" href="/reports/<?= e($name) ?>/<?= e($latest['run_id']) ?>">Voir le rapport</a></p>
    <?php else: ?>
      <p class="opacity">Aucun run enregistré.</p>
    <?php endif; ?>
    <p><a href="/reports/<?= e($name) ?>">Historique (<?= e($env['runs']) ?>)</a></p>
  </article>
<?php endforeach; ?>
</section>

<section class="page-head">
  <h2>Jobs récents</h2>
</section>
<?php if (!empty($jobs)): ?>
<table class="grid">
  <thead><tr><th>Démarré</th><th>Env</th><th>Manifeste</th><th>PHP</th><th>Statut</th><th>Log</th></tr></thead>
  <tbody>
  <?php foreach ($jobs as $job): ?>
    <tr>
      <td class="mono small"><?= e(substr($job->started_at, 0, 19)) ?></td>
      <td><?= e($job->env) ?></td>
      <td><?= e($job->manifest) ?></td>
      <td><?= e($job->php) ?></td>
      <td><span class="badge <?= $job->status === 'done' ? 'ok' : ($job->status === 'failed' ? 'ko' : 'run') ?>"><?= e($job->status) ?></span></td>
      <td><a href="/run?job=<?= e($job->id) ?>">voir</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php else: ?>
<p class="opacity">Aucun job.</p>
<?php endif; ?>
