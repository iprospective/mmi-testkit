<section class="page-head">
  <h1>Lancer des tests</h1>
  <p class="opacity">Le run est lancé en arrière-plan ; l’historique se met à jour à la fin.</p>
  <?php if (!empty($error)): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
</section>

<section class="panel">
  <h2>Choisir un manifeste</h2>
  <form method="get" action="/run" class="inline">
    <select name="manifest">
      <?php foreach ($manifests as $name => $path): ?>
        <option value="<?= e($name) ?>" <?= $name === $selected ? 'selected' : '' ?>><?= e($name) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Charger</button>
  </form>
</section>

<?php if ($selected): ?>
<section class="panel">
  <h2><?= e($selected) ?></h2>
  <form method="post" action="/run">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <input type="hidden" name="manifest" value="<?= e($selected) ?>">
    <p class="opacity">Sous-ensemble optionnel :</p>
    <div class="checks">
      <?php foreach ($suites as $suite): ?>
        <label><input type="checkbox" name="subset[]" value="<?= e($suite) ?>"> <?= e($suite) ?></label>
      <?php endforeach; ?>
    </div>
    <button type="submit">Lancer le run</button>
  </form>
</section>
<?php endif; ?>

<?php if ($job): ?>
<section class="panel">
  <h2>Job <?= e($job->id) ?> <span class="badge <?= $job->status === 'done' ? 'ok' : ($job->status === 'failed' ? 'ko' : 'run') ?>"><?= e($job->status) ?></span></h2>
  <p class="mono small"><?= e($job->env) ?> · <?= e($job->manifest) ?> · PHP <?= e($job->php) ?></p>
  <p><a href="/reports/<?= e($job->env) ?>">Voir l’historique de l’env</a></p>
  <pre class="log"><?= e($log) ?></pre>
</section>
<?php endif; ?>

<h2>Jobs récents</h2>
<?php if (!empty($jobs)): ?>
<table class="grid">
  <thead><tr><th>Démarré</th><th>Env</th><th>Manifeste</th><th>Statut</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($jobs as $item): ?>
    <tr>
      <td class="mono small"><?= e(substr($item->started_at, 0, 19)) ?></td>
      <td><?= e($item->env) ?></td>
      <td><?= e($item->manifest) ?></td>
      <td><span class="badge <?= $item->status === 'done' ? 'ok' : ($item->status === 'failed' ? 'ko' : 'run') ?>"><?= e($item->status) ?></span></td>
      <td><a href="/run?job=<?= e($item->id) ?>">voir</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php else: ?>
<p class="opacity">Aucun job.</p>
<?php endif; ?>
