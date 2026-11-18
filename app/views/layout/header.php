<?php /** @var array $data */
/** @var array $currentUser */
extract($data, EXTR_SKIP);
$nav = $data['nav'] ?? '';
$currentUri = $data['currentUri'] ?? '';
$pendingCount = 0;
if (Auth::isTeacher()) {
    $pendingCount = Database::count('submissions s JOIN assignments a ON s.assignment_id = a.id JOIN courses c ON a.course_id = c.id', 'c.teacher_id = :tid AND s.status = :st', ['tid' => Auth::id(), 'st' => 'submitted']);
}
$notifCount = Auth::check() ? Database::count('notifications', 'user_id = :uid AND is_read = 0', ['uid' => Auth::id()]) : 0;
$recentNotifs = Auth::check() ? Database::fetchAll('SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 6', ['uid' => Auth::id()]) : [];

$dueFlashcards = 0;
if (Auth::check()) {
    try {
        $dueFlashcards = Database::count('flashcard_reviews fr', 'fr.user_id = :uid AND fr.next_review <= :today', ['uid' => Auth::id(), 'today' => date('Y-m-d')]);
    } catch (Throwable $e) {}
}

// Theme
$theme = Auth::check() && !empty(Auth::user()['theme_preference']) ? Auth::user()['theme_preference'] : 'auto';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $theme === 'dark' ? 'dark' : 'light' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#4F46E5">
<meta name="description" content="<?= e(APP_NAME) ?> — assignment-focused LMS. By <?= e(APP_TEAM) ?>.">
<link rel="manifest" href="<?= asset('manifest.json') ?>">
<link rel="icon" type="image/png" href="<?= asset('favicon.png') ?>">
<link rel="apple-touch-icon" href="<?= asset('apple-touch-icon.png') ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= e(APP_NAME) ?>">
<title><?= e($data['pageTitle'] ?? $data['appName'] ?? 'doodle') ?> · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.getRegistrations().then(registrations => {
    registrations.forEach(registration => {
      if ([registration.active, registration.waiting, registration.installing].some(worker =>
          worker && worker.scriptURL === new URL('<?= asset('sw.js') ?>', location.href).href)) registration.unregister();
    });
  }).catch(() => {});
}
if ('caches' in window) caches.keys().then(keys => Promise.all(
  keys.filter(key => key.startsWith('doodle-')).map(key => caches.delete(key))
)).catch(() => {});
</script>
<div class="app-shell">
  <div class="sidebar-backdrop" onclick="document.querySelector('.sidebar').classList.remove('open'); this.classList.remove('show')"></div>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="mark">d</div>
      <div>
        <h1><?= e(APP_NAME) ?></h1>
        <p>by <?= e(APP_TEAM) ?></p>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="sidebar-section">Main</div>
      <a href="<?= url('') ?>" class="<?= $currentUri === '' ? 'active' : '' ?>"><span class="material-icons">home</span> Dashboard</a>

      <?php if (Auth::isStudent()): ?>
        <div class="sidebar-section">Learning</div>
        <a href="<?= url('student') ?>" class="<?= in_array($currentUri, ['student', 'student/index'], true) ? 'active' : '' ?>"><span class="material-icons">dashboard</span> My Learning</a>
        <a href="<?= url('catalog') ?>" class="<?= strpos($currentUri, 'catalog') === 0 ? 'active' : '' ?>"><span class="material-icons">explore</span> Course Catalog</a>
        <a href="<?= url('student/grades') ?>" class="<?= strpos($currentUri, 'student/grades') === 0 ? 'active' : '' ?>"><span class="material-icons">grading</span> My Grades</a>
      <?php endif; ?>

      <?php if (Auth::isTeacher()): ?>
        <div class="sidebar-section">Teaching</div>
        <a href="<?= url('teacher') ?>" class="<?= in_array($currentUri, ['teacher', 'teacher/index'], true) ? 'active' : '' ?>"><span class="material-icons">dashboard</span> Teacher Studio</a>
        <a href="<?= url('teacher/courses') ?>" class="<?= preg_match('~^teacher/(?:courses|editCourse|assignment|gradeSubmission)(?:/|$)~', $currentUri) ? 'active' : '' ?>"><span class="material-icons">school</span> My Courses</a>
        <a href="<?= url('teacher/gradebook') ?>" class="<?= strpos($currentUri, 'teacher/gradebook') === 0 ? 'active' : '' ?>"><span class="material-icons">grading</span> Gradebook
          <?php if ($pendingCount > 0): ?><span class="badge"><?= $pendingCount ?></span><?php endif; ?>
        </a>
      <?php endif; ?>

      <?php if (Auth::isAdmin()): ?>
        <div class="sidebar-section">Administration</div>
        <a href="<?= url('admin') ?>" class="<?= in_array($currentUri, ['admin', 'admin/index'], true) ? 'active' : '' ?>"><span class="material-icons">admin_panel_settings</span> Admin Panel</a>
        <a href="<?= url('admin/users') ?>" class="<?= strpos($currentUri, 'admin/users') === 0 ? 'active' : '' ?>"><span class="material-icons">group</span> Users</a>
        <a href="<?= url('admin/courses') ?>" class="<?= strpos($currentUri, 'admin/courses') === 0 ? 'active' : '' ?>"><span class="material-icons">school</span> All Courses</a>
        <a href="<?= url('admin/categories') ?>" class="<?= strpos($currentUri, 'admin/categories') === 0 ? 'active' : '' ?>"><span class="material-icons">category</span> Categories</a>
        <a href="<?= url('admin/announcements') ?>" class="<?= strpos($currentUri, 'admin/announcements') === 0 ? 'active' : '' ?>"><span class="material-icons">campaign</span> Announcements</a>
        <a href="<?= url('admin/backups') ?>" class="<?= strpos($currentUri, 'admin/backups') === 0 ? 'active' : '' ?>"><span class="material-icons">backup</span> Backups</a>
        <a href="<?= url('admin/settings') ?>" class="<?= strpos($currentUri, 'admin/settings') === 0 ? 'active' : '' ?>"><span class="material-icons">settings</span> Settings</a>
      <?php endif; ?>

      <?php if (Auth::check()): ?>
        <div class="sidebar-section">Study Tools</div>
        <a href="<?= url('flashcards') ?>" class="<?= strpos($currentUri, 'flashcards') === 0 ? 'active' : '' ?>"><span class="material-icons">style</span> Flashcards
          <?php if ($dueFlashcards > 0): ?><span class="badge"><?= $dueFlashcards ?></span><?php endif; ?>
        </a>
        <a href="<?= url('calendar') ?>" class="<?= strpos($currentUri, 'calendar') === 0 ? 'active' : '' ?>"><span class="material-icons">calendar_month</span> Calendar</a>

        <div class="sidebar-section">Account</div>
        <a href="<?= url('auth/profile') ?>" class="<?= $currentUri === 'auth/profile' ? 'active' : '' ?>"><span class="material-icons">person</span> Profile</a>
        <a href="<?= url('auth/setup2fa') ?>" class="<?= $currentUri === 'auth/setup2fa' ? 'active' : '' ?>"><span class="material-icons">verified_user</span> Two-factor (2FA)</a>
        <a href="#" onclick="toggleTheme(); return false;"><span class="material-icons">dark_mode</span> Toggle theme</a>
        <a href="<?= url('auth/logout') ?>"><span class="material-icons">logout</span> Sign out</a>
      <?php else: ?>
        <div class="sidebar-section">Account</div>
        <a href="<?= url('auth/login') ?>"><span class="material-icons">login</span> Sign in</a>
        <a href="<?= url('auth/register') ?>"><span class="material-icons">person_add</span> Create account</a>
      <?php endif; ?>
    </nav>

    <div class="sidebar-foot">
      <p>v<?= APP_VERSION ?> · GPL-3.0</p>
      <p><a href="<?= url('') ?>"><?= e(APP_NAME) ?></a> · <?= e(APP_TEAM) ?></p>
    </div>
  </aside>

  <div class="main-area">
    <header class="topbar">
      <button class="menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open'); document.querySelector('.sidebar-backdrop').classList.toggle('show')">
        <span class="material-icons">menu</span>
      </button>



      <div class="topbar-actions">
        <?php if (Auth::check()): ?>


          <div class="relative">
            <button class="topbar-icon-btn" onclick="document.getElementById('notifDrop').classList.toggle('open')">
              <span class="material-icons">notifications</span>
              <?php if ($notifCount > 0): ?><span class="dot"></span><?php endif; ?>
            </button>
            <div class="notif-dropdown" id="notifDrop">
              <div class="notif-head">
                <span>Notifications</span>
                <?php if ($notifCount > 0): ?><a href="<?= url('notifications/markAllRead') ?>" class="btn btn-ghost btn-sm">Mark all read</a><?php endif; ?>
              </div>
              <?php if (empty($recentNotifs)): ?>
                <div style="padding:30px;text-align:center;color:var(--text-muted);font-size:.85rem;">No notifications</div>
              <?php else: ?>
                <?php foreach ($recentNotifs as $n): ?>
                  <a href="<?= $n['link'] && Router::resolve(explode('?', $n['link'], 2)[0]) !== null ? url($n['link']) : '#' ?>" class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>" style="display:block;text-decoration:none;color:inherit;">
                    <div class="notif-title"><?= e($n['title']) ?></div>
                    <div class="notif-body"><?= e(truncate($n['body'], 100)) ?></div>
                    <div class="notif-time"><?= timeAgo($n['created_at']) ?></div>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <a href="<?= url('auth/profile') ?>" class="user-chip" style="text-decoration:none;color:inherit;">
            <span class="avatar" style="background:<?= avatarColor($currentUser['full_name']) ?>;">
              <?php if (!empty($currentUser['avatar'])): ?><img src="<?= uploadUrl($currentUser['avatar']) ?>" alt=""><?php else: ?><?= initials($currentUser['full_name']) ?><?php endif; ?>
            </span>
            <span class="name"><?= e($currentUser['full_name']) ?></span>
          </a>
        <?php else: ?>
          <a href="<?= url('auth/login') ?>" class="btn btn-secondary">Sign in</a>
          <a href="<?= url('auth/register') ?>" class="btn btn-primary">Get started</a>
        <?php endif; ?>
      </div>
    </header>

    <main class="content">
      <?= renderFlash($data['flash'] ?? []) ?>

<script>
function toggleTheme() {
  const html = document.documentElement;
  const cur = html.getAttribute('data-theme') || 'light';
  const next = cur === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', next);
  // Persist via fetch
  fetch('<?= url("auth/setTheme") ?>', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'theme=' + next + '&<?= CSRF_TOKEN_NAME ?>=<?= CSRF::token() ?>' });
}
// Auto dark mode if user preference is 'auto' and OS prefers dark
(function() {
  const html = document.documentElement;
  if (html.getAttribute('data-theme') === 'auto' || !html.getAttribute('data-theme')) {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      html.setAttribute('data-theme', 'dark');
    } else {
      html.setAttribute('data-theme', 'light');
    }
  }
})();
</script>
