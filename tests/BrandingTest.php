<?php
declare(strict_types=1);
require_once __DIR__.'/Support/WebFixture.php';

return ['group'=>'branding','tests'=>[
    'public and three-role pages use LMSTotal group branding without old lettermarks' => function():void {
        $web=new WebFixture();
        foreach (['','auth/login','auth/register'] as $route) {
            $page=$web->request('guest',$route); WebFixture::pageOk($page,'Branding '.$route);
            if (!str_contains($page['body'],'LMSTotal') || !str_contains($page['body'],'by group MuscleMan')) throw new RuntimeException('Public branding is not updated.');
            if (preg_match('/class="(?:brand-mark|mark)"|by Death Legion Team|Join the doodle/',$page['body'])) throw new RuntimeException('Old visible branding remains.');
        }
        foreach (['student','teacher','admin'] as $role) {
            $web->login($role); $page=$web->request($role,$role); WebFixture::pageOk($page,$role.' branding');
            if (!str_contains($page['body'],'LMSTotal') || !str_contains($page['body'],'by group MuscleMan')) throw new RuntimeException('Role branding is not updated.');
            if (preg_match('/class="(?:brand-mark|mark)"|auth\/setup2fa|rel="(?:manifest|apple-touch-icon)"|favicon\.png/',$page['body'])) throw new RuntimeException('Unsupported setup or logo entry remains.');
        }
    },
    'two-factor setup route is denied for guests and all roles while login challenge remains available' => function():void {
        $web=new WebFixture();
        foreach (['guest','student','teacher','admin'] as $role) {
            if ($role!=='guest') $web->login($role);
            foreach (['auth/setup2fa','Auth/setup2fa'] as $route) {
                if ($web->request($role,$route)['status']!==404) throw new RuntimeException('Unsupported 2FA setup route still exposed.');
            }
        }
        $source=file_get_contents(dirname(__DIR__).'/app/views/auth/login.php');
        if (!str_contains($source,'requires2fa') || !str_contains($source,'totp_code')) throw new RuntimeException('Existing account challenge removed.');
    },
    'installer renders LMSTotal defaults without a lettermark in a disposable target' => function():void {
        $web=new WebFixture();
        [$code]=$web->php(['-r',"unlink('.installed');"]);
        if ($code!==0) throw new RuntimeException('Cannot prepare disposable installer.');
        $page=$web->request('guest',$web->baseUrl.'/install.php'); WebFixture::pageOk($page,'Installer branding');
        if (!str_contains($page['body'],'LMSTotal') || !str_contains($page['body'],'by group MuscleMan') || str_contains($page['body'],'brand-mark')) throw new RuntimeException('Installer brand/defaults are stale.');
    },
]];
