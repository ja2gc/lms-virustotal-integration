<section class="card card-pad submission-scan" style="margin-top:16px;overflow-wrap:anywhere;min-width:0"
  data-scan-status="<?= e(url('scan/status/'.$submissionId)) ?>"
  data-scan-file="<?= e((string)($file['fileId']??'')) ?>" data-scan-download-base="<?= e(url('submissionFile/download/')) ?>"
  <?php if ($teacher): ?>data-scan-history="<?= e(url('scan/history/'.$submissionId)) ?>"<?php endif; ?>>
  <h3>File security advisory</h3>
  <p class="badge badge-info" data-scan-evidence <?= scanEvidenceLabel($context)===''?'hidden':'' ?>><?= e(scanEvidenceLabel($context)) ?></p>
  <p role="status" aria-live="polite" data-scan-label><?= e(scanDisplay($context)) ?></p>
  <p class="text-muted" data-scan-reason><?= e(str_replace('_',' ',$context['reason']??'')) ?></p>
  <p class="text-muted" data-scan-time><?= !empty($context['checkedAt'])?'Checked '.e($context['checkedAt']):'' ?></p>
  <p role="alert" data-scan-error></p>
    <a data-scan-download class="btn btn-secondary" <?php if ($file): ?>href="<?= e(url('submissionFile/download/'.$file['fileId'])) ?>"<?php else: ?>hidden<?php endif; ?>>Download <?= e($file['originalName']??'current attachment') ?></a>
  <?php if ($file): ?>
    <p class="text-muted">An advisory is evidence at a point in time, not a guarantee. Use care when opening attachments.</p>
  <?php endif; ?>
  <?php if ($teacher): ?>
    <form data-scan-rescan action="<?= e(url('scan/rescan/'.$submissionId)) ?>" method="post" style="margin-top:12px">
      <input type="hidden" name="<?= e(CSRF_TOKEN_NAME) ?>" value="<?= e(CSRF::token()) ?>">
      <input type="hidden" name="requestToken" value="">
      <button class="btn btn-secondary" type="submit" disabled>Request a fresh scan</button>
      <p class="text-muted">A fresh request creates another provider analysis. Pending work remains separate from prior results.</p>
    </form>
    <details style="margin-top:12px"><summary>Scan history and counts</summary><div data-scan-history-list><?php require __DIR__.'/_history.php'; ?></div></details>
  <?php endif; ?>
  <noscript>Automatic status refresh and fresh scan requests require JavaScript. Reload to see the latest stored advisory.</noscript>
</section>
