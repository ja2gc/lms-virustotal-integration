<?php if (!$history): ?>
  <p class="text-muted">No scan attempts recorded for this attachment.</p>
<?php else: ?>
  <ol style="padding-left:22px;overflow-wrap:anywhere">
  <?php foreach ($history as $attempt): ?>
    <li style="margin:12px 0">
      <strong><?= e(scanDisplay($attempt)) ?></strong>
      <?php if (scanEvidenceLabel($attempt)!==''): ?><p class="badge badge-info"><?= e(scanEvidenceLabel($attempt)) ?></p><?php endif; ?>
      <span> · <?= e($attempt['origin']==='teacher_rescan'?'Teacher request':'Automatic submission') ?></span>
      <p><?= e(str_replace('_',' ',$attempt['reason']??'')) ?></p>
      <?php if (!empty($attempt['completedAt'])): ?><p>Checked <?= e($attempt['completedAt']) ?></p><?php endif; ?>
      <?php if (!empty($attempt['stats'])): ?><p><?php foreach ($attempt['stats'] as $category=>$count): ?><span style="display:inline-block;margin-right:10px"><?= e($category) ?>: <?= e((string)$count) ?></span><?php endforeach; ?></p><?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ol>
<?php endif; ?>
