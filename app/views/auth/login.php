<?php /** @var array $data */ extract($data, EXTR_SKIP); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Sign in') ?> · <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
</head>
<body class="auth-body">
<div class="auth-wrap">
  <div class="auth-card">
    <div class="brand">
      <div class="brand-mark">d</div>
      <div class="brand-text">
        <h1><?= e(APP_NAME) ?></h1>
        <p>by <?= e(APP_TEAM) ?> · GPL-3.0</p>
      </div>
    </div>

    <h2><?= isset($requires2fa) && $requires2fa ? 'Two-factor authentication' : 'Welcome back' ?></h2>
    <p class="subtitle"><?= isset($requires2fa) && $requires2fa ? 'Enter the 6-digit code from your authenticator app.' : 'Sign in to continue your learning journey.' ?></p>

    <?= renderFlash($flash ?? []) ?>

    <?php if (isset($requires2fa) && $requires2fa): ?>
      <form method="post" action="<?= url('auth/login') ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <div class="form-group">
          <label>Authentication code</label>
          <input type="text" name="totp_code" pattern="\d{6}" maxlength="6" required autofocus
                 style="font-size:1.6rem;letter-spacing:.4em;text-align:center;font-family:monospace;"
                 placeholder="000000" inputmode="numeric">
          <span class="hint">Or use one of your backup codes (format: XXXX-XXXX).</span>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg"><span class="material-icons" style="vertical-align:middle;">verified_user</span> Verify & sign in</button>
      </form>
      <p class="auth-alt"><a href="<?= url('auth/logout') ?>">Cancel and sign out</a></p>
    <?php else: ?>
      <form method="post" action="<?= url('auth/login') ?>">
        <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">
        <div class="form-group">
          <label>Username or email</label>
          <input type="text" name="identifier" required autofocus value="<?= e($_POST['identifier'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" required>
        </div>
        <div class="form-group checkbox-group">
          <input type="checkbox" name="remember" id="remember" value="1">
          <label for="remember">Keep me signed in</label>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Sign in</button>
      </form>

      <p class="auth-alt">Don't have an account? <a href="<?= url('auth/register') ?>">Create one</a></p>
    <?php endif; ?>
    <p class="auth-foot"><?= e(APP_NAME) ?> v<?= APP_VERSION ?> · <?= e(APP_TEAM) ?> · open source under GPL-3.0</p>
  </div>
</div>
</body>
</html>
