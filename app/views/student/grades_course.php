<?php /** @var array $data */
extract($data, EXTR_SKIP);
$c = $course;
?>
<div class="breadcrumb">
  <a href="<?= url('student/grades') ?>">My grades</a>
  <span class="material-icons">chevron_right</span>
  <span><?= e($c['title']) ?></span>
</div>

<div class="page-head">
  <div class="title-block">
    <h1>My Grades · <?= e($c['title']) ?></h1>
    <p>Detailed breakdown of your performance in this course.</p>
  </div>
  <div class="actions">
    <a href="<?= url('course/view/' . $c['id']) ?>" class="btn btn-secondary"><span class="material-icons">school</span> Back to course</a>
  </div>
</div>

<div class="grid grid-2" style="align-items:flex-start;">
  <div class="card">
    <div class="card-head"><h3>Assignments & graded items</h3></div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Item</th><th>Score</th><th>Max</th><th>%</th><th>Graded</th></tr></thead>
        <tbody>
          <?php if (empty($grades)): ?>
            <tr><td colspan="5" class="text-center text-muted" style="padding:24px;">No grades recorded yet.</td></tr>
          <?php else: foreach ($grades as $g):
            $pct = $g['max_score'] > 0 ? round(($g['score'] / $g['max_score']) * 100, 1) : 0;
            $color = $pct >= 80 ? 'success' : ($pct >= 60 ? 'warning' : 'danger');
          ?>
            <tr>
              <td>
                <strong><?= e($g['item_name']) ?></strong>
                <span class="badge" style="margin-left:6px;"><?= ucfirst($g['item_type']) ?></span>
                <?php if (!empty($g['feedback'])): ?>
                  <div class="text-muted" style="font-size:.78rem;margin-top:4px;font-style:italic;">"<?= e($g['feedback']) ?>"</div>
                <?php endif; ?>
              </td>
              <td><strong><?= $g['score'] ?></strong></td>
              <td class="text-muted"><?= $g['max_score'] ?></td>
              <td><span class="badge badge-<?= $color ?>"><?= $pct ?>%</span></td>
              <td class="text-muted" style="font-size:.82rem;"><?= timeAgo($g['graded_at']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
