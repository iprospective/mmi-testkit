<section class="page-head">
  <h1>Rapports</h1>
  <p class="opacity">Historique des runs par environnement.</p>
</section>

<?php foreach ($envs as $name => $env): ?>
  <h2><?= e($env['label'] ?: $name) ?> <span class="mono small"><?= e($name) ?></span></h2>
  <?php if (!empty($env['runs'])): ?>
  <table class="grid">
    <thead><tr><th>Date</th><th>Commit</th><th>Branche</th><th>PHP</th><th>Contrôles</th><th>Échecs</th><th>Warn.</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($env['runs'] as $run): ?>
      <tr>
        <td class="mono small"><?= e(substr($run['date'], 0, 19)) ?></td>
        <td class="mono"><?= e(substr($run['code']['commit'], 0, 8)) ?></td>
        <td><?= e($run['code']['branch']) ?></td>
        <td><?= e($run['php']) ?></td>
        <td><?= e($run['summary']['checks']) ?></td>
        <td class="<?= $run['summary']['failed'] ? 'ko-text' : '' ?>"><?= e($run['summary']['failed']) ?></td>
        <td><?= e($run['summary']['warnings']) ?></td>
        <td><a href="/reports/<?= e($name) ?>/<?= e($run['run_id']) ?>">ouvrir</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p class="opacity">Aucun run.</p>
  <?php endif; ?>
<?php endforeach; ?>
