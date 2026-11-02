<?php $summary = $report['summary']; $ok = !empty($summary['ok']); ?>
<section class="page-head">
  <p><a href="/reports">← Rapports</a></p>
  <h1>Rapport — <?= e($envLabel) ?></h1>
  <p class="mono small"><?= e($report['run_id']) ?> · <?= e(substr($report['date'], 0, 19)) ?> · commit <?= e(substr($report['code']['commit'], 0, 8)) ?> (<?= e($report['code']['branch']) ?>) · PHP <?= e($report['php']) ?></p>
  <p><span class="badge <?= $ok ? 'ok' : 'ko' ?>"><?= $ok ? 'OK' : 'ÉCHEC' ?></span>
    <?= e($summary['checks']) ?> contrôles, <?= e($summary['failed']) ?> échec(s), <?= e($summary['warnings']) ?> avertissement(s)</p>
  <p><a class="button" href="/reports/<?= e($env) ?>/<?= e($report['run_id']) ?>/json">JSON brut</a></p>
</section>

<?php foreach ($report['suites'] as $suite): ?>
  <h2><?= e($suite['suite']) ?> <span class="badge <?= $suite['failed'] ? 'ko' : 'ok' ?>"><?= $suite['failed'] ? 'ÉCHEC' : 'OK' ?></span></h2>
  <p class="opacity"><?= e($suite['passed']) ?> / <?= e($suite['checks_count']) ?> contrôles passés</p>
  <?php if (!empty($suite['warnings'])): ?>
    <ul class="list-warn">
    <?php foreach ($suite['warnings'] as $warning): ?>
      <li><strong><?= e($warning['label']) ?></strong> — <?= e($warning['message']) ?></li>
    <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <?php if (!empty($suite['failures'])): ?>
    <ul class="list-fail">
    <?php foreach ($suite['failures'] as $failure): ?>
      <li><strong><?= e($failure['label']) ?></strong> — got <?= e(json_encode($failure['got'])) ?>, want <?= e(json_encode($failure['want'])) ?></li>
    <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <details>
    <summary>Détail des <?= e($suite['checks_count']) ?> contrôles</summary>
    <table class="grid">
      <thead><tr><th>Contrôle</th><th>Résultat</th><th>Got</th><th>Want</th></tr></thead>
      <tbody>
      <?php foreach ($suite['checks'] as $check): ?>
        <tr class="<?= $check['ok'] ? '' : 'row-fail' ?>">
          <td><?= e($check['label']) ?></td>
          <td><?= $check['ok'] ? 'OK' : 'ÉCHEC' ?></td>
          <td class="mono small"><?= e(json_encode($check['got'])) ?></td>
          <td class="mono small"><?= e(json_encode($check['want'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </details>
<?php endforeach; ?>
