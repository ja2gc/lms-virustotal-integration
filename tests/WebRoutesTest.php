<?php
declare(strict_types=1);
require_once __DIR__ . '/Support/WebFixture.php';

return ['group' => 'routing', 'tests' => [
    'home delegation preserves the one-time login message for every role' => function (): void {
        $web = new WebFixture();
        foreach (['admin','teacher','student'] as $role) {
            $web->login($role);
            $home = $web->request($role, '');
            WebFixture::pageOk($home, $role . ' home');
            if (!str_contains($home['body'],'Welcome back, Fixture '.$role.'!')) throw new RuntimeException('Home lost the '.$role.' flash message.');
        }
    },
    'every literal application link and redirect resolves to an action' => function (): void {
        if (!defined('APP_DIR')) define('APP_DIR', dirname(__DIR__) . '/app');
        require_once APP_DIR . '/core/Controller.php';
        require_once APP_DIR . '/core/Router.php';
        $excluded = static function (string $route): bool {
            return (bool) preg_match('~^(?:(?:ai|analytics|api|certificate|certificates|code|forum|gamification|group|learningpath|learning_paths|message|messages|oauth|poll|push|quiz|review|whiteboard|wiki)(?:/|$)|teacher/(?:quizzes|createQuiz|quiz|addQuestion|deleteQuestion)(?:/|$)|flashcards?/generateFromCourse(?:/|$))~i', $route);
        };
        $failures = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APP_DIR, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') continue;
            preg_match_all('~\b(?:url|redirect)\(\s*([\'"])([^\'"]*)\1~', file_get_contents($file->getPathname()), $matches);
            foreach ($matches[2] as $prefix) {
                $route = explode('?', $prefix, 2)[0];
                if (str_ends_with($route, '/')) $route .= '1';
                $resolved = Router::resolve($route);
                if ($excluded($route)) {
                    if ($resolved !== null) $failures[] = 'Excluded route still resolves: ' . $prefix;
                } elseif ($resolved === null) $failures[] = $prefix;
            }
        }
        if ($failures) throw new RuntimeException('Unresolved links: ' . implode(', ', array_unique($failures)));
        foreach (['flashcards'=>'FlashcardController','flashcard'=>'FlashcardController','admin/backups'=>'BackupController','backup'=>'BackupController'] as $route=>$class) {
            if ((Router::resolve($route)[0] ?? null) !== $class) throw new RuntimeException('Wrong controller for ' . $route);
        }
    },
    'query links keep routing separate from filter parameters' => function (): void {
        if (!defined('BASE_URL')) define('BASE_URL', 'http://127.0.0.1');
        require_once dirname(__DIR__) . '/app/helpers/functions.php';
        parse_str(parse_url(url('calendar?m=10&y=2026'), PHP_URL_QUERY), $query);
        if ($query !== ['r'=>'calendar','m'=>'10','y'=>'2026']) throw new RuntimeException('Calendar/filter query is embedded in the route.');
    },
    'every sidebar destination renders for each of the three roles' => function (): void {
        $web = new WebFixture();
        $failures = [];
        foreach (['admin', 'teacher', 'student'] as $role) {
            $web->login($role);
            $home = $web->request($role, $role);
            WebFixture::pageOk($home, $role . ' dashboard');
            preg_match('~<nav class="sidebar-nav">(.*?)</nav>~s', $home['body'], $nav);
            preg_match_all('/href="([^"]+)"/', $nav[1] ?? '', $links);
            foreach (array_unique($links[1]) as $url) {
                $url = html_entity_decode($url, ENT_QUOTES);
                if (str_contains($url, '#') || str_contains($url, 'auth/logout')) continue;
                try { WebFixture::pageOk($web->request($role, $url), $role . ' ' . (parse_url($url, PHP_URL_QUERY) ?: 'home')); }
                catch (Throwable $e) { $failures[] = $e->getMessage(); }
            }
        }
        if ($failures) throw new RuntimeException(implode('; ', $failures));
    },
    'restricted role routes stay forbidden and guests are redirected to login' => function (): void {
        $web = new WebFixture();
        foreach (['student', 'teacher'] as $role) {
            $web->login($role);
            foreach (['admin', 'admin/users', 'admin/backups', 'backup'] as $route) {
                if ($web->request($role, $route)['status'] !== 403) throw new RuntimeException($role . ' must be denied ' . $route);
            }
        }
        if ($web->request('student', 'teacher')['status'] !== 403) throw new RuntimeException('Student reached teacher area.');
        foreach (['student', 'teacher', 'admin', 'flashcards', 'notifications', 'calendar'] as $route) {
            $response = $web->request('guest', $route);
            if ($response['status'] !== 302 || !str_contains($response['location'] ?? '', 'auth/login')) throw new RuntimeException('Guest did not require login: ' . $route);
        }
        foreach (['teacher/__construct', 'teacher/render', 'teacher/editCourse', 'teacher/editCourse/nope', 'teacher/editCourse/999999999999999999999999', 'missing', 'learning_paths/missing'] as $route) {
            if ($web->request('teacher', $route)['status'] !== 404) throw new RuntimeException('Malformed/unknown route must return 404: ' . $route);
        }
    },
]];
