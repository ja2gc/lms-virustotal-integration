<?php /** @var array $data */
extract($data, EXTR_SKIP);
$s = $settings;
?>
<div class="page-head">
  <div class="title-block">
    <h1>System Settings</h1>
    <p>Configure the platform's global behavior and branding.</p>
  </div>
</div>

<div class="tabs">
  <a href="#general" class="active" onclick="switchTab(event,'general')">General</a>
  <a href="#security" onclick="switchTab(event,'security')">Security</a>
  <a href="#email" onclick="switchTab(event,'email')">Email / SMTP</a>
  <a href="#maintenance" onclick="switchTab(event,'maintenance')">Maintenance</a>
</div>

<div class="card card-pad">
  <form method="post" action="<?= url('admin/settings') ?>">
    <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= CSRF::token() ?>">

    <!-- General -->
    <div id="general" class="tab-pane">
      <h3 style="margin-bottom: 14px;">Site branding</h3>
      <div class="form-grid">
        <div class="form-group"><label>Site name</label><input type="text" name="site_name" value="<?= e($s['site_name'] ?? APP_NAME) ?>"></div>
        <div class="form-group"><label>Site tagline</label><input type="text" name="site_tagline" value="<?= e($s['site_tagline'] ?? '') ?>"></div>
      </div>
      <div class="form-group"><label>Site description (for SEO / meta)</label><textarea name="site_description" rows="2"><?= e($s['site_description'] ?? '') ?></textarea></div>
      <div class="form-group"><label>Footer text</label><input type="text" name="footer_text" value="<?= e($s['footer_text'] ?? '') ?>"></div>
      <div class="form-group"><label>Theme primary color (hex)</label><input type="text" name="theme_primary" value="<?= e($s['theme_primary'] ?? '#4F46E5') ?>"></div>

      <hr style="border:0;border-top:1px solid var(--border);margin:24px 0;">
      <h3 style="margin-bottom: 14px;">Registration & accounts</h3>
      <div class="form-grid">
        <div class="form-group"><label>Allow public registration?</label><select name="allow_registration"><option value="1" <?= ($s['allow_registration'] ?? '1') === '1' ? 'selected' : '' ?>>Yes — anyone can register</option><option value="0" <?= ($s['allow_registration'] ?? '1') === '0' ? 'selected' : '' ?>>No — admins must create accounts</option></select></div>
        <div class="form-group"><label>Default role</label><select name="default_role"><option value="student" <?= ($s['default_role'] ?? 'student') === 'student' ? 'selected' : '' ?>>Student</option><option value="teacher" <?= ($s['default_role'] ?? 'student') === 'teacher' ? 'selected' : '' ?>>Teacher</option></select></div>
      </div>
      <div class="form-group"><label>Contact email</label><input type="email" name="contact_email" value="<?= e($s['contact_email'] ?? '') ?>"></div>
      <div class="form-group"><label>Default language</label><select name="default_language"><option value="en" <?= ($s['default_language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option><option value="es">Español</option><option value="fr">Français</option><option value="de">Deutsch</option><option value="si">සිංහල</option><option value="ta">தமிழ்</option></select></div>
    </div>

    <!-- Security -->
    <div id="security" class="tab-pane" style="display:none;">
      <h3 style="margin-bottom:14px;">Login security</h3>
      <div class="form-grid">
        <div class="form-group"><label>Max login attempts before lockout</label><input type="number" name="login_max_attempts" value="<?= e($s['login_max_attempts'] ?? '5') ?>" min="1" max="20"></div>
        <div class="form-group"><label>Lockout duration (minutes)</label><input type="number" name="login_lockout_minutes" value="<?= e($s['login_lockout_minutes'] ?? '15') ?>" min="1" max="1440"></div>
      </div>
      <hr style="border:0;border-top:1px solid var(--border);margin:24px 0;">
      <h3 style="margin-bottom:14px;">reCAPTCHA (optional)</h3>
      <p class="text-muted" style="font-size:.85rem;margin-bottom:10px;">Protect login/registration forms with Google reCAPTCHA v3. Leave blank to disable.</p>
      <div class="form-grid">
        <div class="form-group"><label>reCAPTCHA site key</label><input type="text" name="recaptcha_site_key" value="<?= e($s['recaptcha_site_key'] ?? '') ?>"></div>
        <div class="form-group"><label>reCAPTCHA secret key</label><input type="password" name="recaptcha_secret_key" value="<?= e($s['recaptcha_secret_key'] ?? '') ?>"></div>
      </div>
    </div>

    <!-- Email -->
    <div id="email" class="tab-pane" style="display:none;">
      <h3 style="margin-bottom:14px;">SMTP configuration</h3>
      <p class="text-muted" style="font-size:.85rem;margin-bottom:10px;">Configure outbound email for notifications. Leave blank to disable email sending.</p>
      <div class="form-group checkbox-group"><input type="checkbox" name="enable_email" id="enableEmail" value="1" <?= ($s['enable_email'] ?? '0') === '1' ? 'checked' : '' ?>><label for="enableEmail">Enable email notifications</label></div>
      <div class="form-grid">
        <div class="form-group"><label>SMTP host</label><input type="text" name="smtp_host" value="<?= e($s['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com"></div>
        <div class="form-group"><label>SMTP port</label><input type="number" name="smtp_port" value="<?= e($s['smtp_port'] ?? '587') ?>"></div>
      </div>
      <div class="form-grid">
        <div class="form-group"><label>SMTP username</label><input type="text" name="smtp_user" value="<?= e($s['smtp_user'] ?? '') ?>"></div>
        <div class="form-group"><label>SMTP password</label><input type="password" name="smtp_pass" value="<?= e($s['smtp_pass'] ?? '') ?>"></div>
      </div>
      <div class="form-grid">
        <div class="form-group"><label>From email</label><input type="email" name="smtp_from_email" value="<?= e($s['smtp_from_email'] ?? '') ?>"></div>
        <div class="form-group"><label>From name</label><input type="text" name="smtp_from_name" value="<?= e($s['smtp_from_name'] ?? APP_NAME) ?>"></div>
      </div>
    </div>

    <!-- Maintenance -->
    <div id="maintenance" class="tab-pane" style="display:none;">
      <h3 style="margin-bottom:14px;">Maintenance mode</h3>
      <p class="text-muted" style="font-size:.85rem;margin-bottom:10px;">When enabled, the site shows a maintenance page to all non-admin users. Admins can still access everything.</p>
      <div class="form-group checkbox-group"><input type="checkbox" name="maintenance_mode" id="maintMode" value="1" <?= ($s['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>><label for="maintMode">Enable maintenance mode</label></div>
      <div class="form-group"><label>Maintenance message</label><textarea name="maintenance_message" rows="3"><?= e($s['maintenance_message'] ?? '') ?></textarea></div>
    </div>

    <hr style="border:0;border-top:1px solid var(--border);margin:24px 0;">
    <h3 style="margin-bottom: 14px;">System information</h3>
    <div style="font-size:.85rem;line-height:1.9;color:var(--text-muted);">
      <div class="flex-between"><span><?= e(APP_NAME) ?> version</span><strong><?= APP_VERSION ?></strong></div>
      <div class="flex-between"><span>PHP version</span><strong><?= PHP_VERSION ?></strong></div>
      <div class="flex-between"><span>Database</span><strong><?= e(strtoupper(DB_TYPE)) ?></strong></div>
      <div class="flex-between"><span>License</span><strong>GPL-3.0</strong></div>
      <div class="flex-between"><span>Built by</span><strong><?= e(APP_TEAM) ?></strong></div>
    </div>

    <hr style="border:0;border-top:1px solid var(--border);margin:24px 0;">
    <button type="submit" class="btn btn-primary btn-lg"><span class="material-icons">save</span> Save settings</button>
  </form>
</div>

<script>
function switchTab(e, id) {
  e.preventDefault();
  document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
  document.querySelectorAll('.tabs a').forEach(a => a.classList.remove('active'));
  document.getElementById(id).style.display = 'block';
  e.target.classList.add('active');
}
</script>
