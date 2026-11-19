<?php
declare(strict_types=1);
require_once __DIR__.'/Support/WebFixture.php';
function releasePost(WebFixture $web,string $role,string $route,array $fields): array
{
    $page=$web->request($role,$role);WebFixture::pageOk($page,$role.' CSRF page');
    $result=$web->request($role,$route,['_csrf_token'=>WebFixture::csrf($page)]+$fields);
    if(!in_array($result['status'],[200,302],true))throw new RuntimeException('Release action failed: '.$route.' HTTP '.$result['status']);
    return $result;
}
function releaseScalar(WebFixture $web,string $sql): string
{
    [$code,$value]=$web->php(['-r',"require 'config/config.php';require 'config/database.php';echo Database::getInstance()->query(\$argv[1])->fetchColumn();",$sql]);
    if($code!==0)throw new RuntimeException('Fixture persistence read failed.');
    return trim($value);
}
return ['group'=>'release','tests'=>[
    'teacher publishes course lesson and assignment that an enrolled student can complete' => function():void {
        $web=new WebFixture();$web->login('teacher');$web->login('student');
        releasePost($web,'teacher','teacher/createCourse',['title'=>'Release course','description'=>'Synthetic release course']);
        $course=releaseScalar($web,"SELECT id FROM courses WHERE title='Release course'");
        if($course==='')throw new RuntimeException('Course creation not persisted.');
        releasePost($web,'teacher','teacher/addModule/'.$course,['title'=>'Release module']);
        $module=releaseScalar($web,"SELECT id FROM modules WHERE title='Release module'");
        releasePost($web,'teacher','teacher/addLesson/'.$module,['title'=>'Release lesson','content_type'=>'text','content'=>'Synthetic learning content']);
        $lesson=releaseScalar($web,"SELECT id FROM lessons WHERE title='Release lesson'");
        releasePost($web,'teacher','teacher/createAssignment',['course_id'=>$course,'title'=>'Release reflection','description'=>'Write a reflection','max_score'=>100]);
        releasePost($web,'teacher','teacher/editCourse/'.$course,['action'=>'publish']);
        $catalog=$web->request('student','catalog');WebFixture::pageOk($catalog,'Published catalog');
        if(!str_contains($catalog['body'],'Release course'))throw new RuntimeException('Published course absent from catalog.');
        releasePost($web,'student','course/enroll/'.$course,[]);
        $learn=$web->request('student','course/learn/'.$course.'/'.$lesson);
        if(preg_match('~(?:Fatal error|Uncaught).{0,700}~s',strip_tags($learn['body']),$error))throw new RuntimeException($error[0]);
        WebFixture::pageOk($learn,'Learning content');
        if(!str_contains($learn['body'],'Synthetic learning content'))throw new RuntimeException('Lesson content absent.');
        $completed=releasePost($web,'student','course/completeLesson/'.$lesson,[]);
        if((json_decode($completed['body'],true)['progress']??null)!=100)throw new RuntimeException('Lesson completion did not persist progress.');
        $assignment=releaseScalar($web,"SELECT id FROM assignments WHERE title='Release reflection'");
        WebFixture::pageOk($web->request('student','student/submitAssignment/'.$assignment),'New assignment submission');
    },
    'admin user category and settings changes plus personal profile calendar and theme persist' => function():void {
        $web=new WebFixture();$web->login('admin');$web->login('student');
        releasePost($web,'admin','admin/createUser',['username'=>'release_person','email'=>'release@example.test','full_name'=>'Release Person','password'=>'SyntheticOnly!2026','role'=>'student']);
        $user=releaseScalar($web,"SELECT id FROM users WHERE username='release_person'");
        if($user==='')throw new RuntimeException('Admin user creation failed.');
        releasePost($web,'admin','admin/editUser/'.$user,['full_name'=>'Updated Release Person','email'=>'release@example.test','role'=>'teacher','status'=>'active']);
        if(releaseScalar($web,'SELECT role FROM users WHERE id='.$user)!=='teacher')throw new RuntimeException('Admin role update failed.');
        releasePost($web,'admin','admin/categories',['name'=>'Release category','description'=>'Synthetic category']);
        $category=releaseScalar($web,"SELECT id FROM categories WHERE name='Release category'");
        if($category==='')throw new RuntimeException('Category creation failed.');
        releasePost($web,'admin','admin/settings',['site_name'=>'LMSTotal','site_tagline'=>'Release verification','allow_registration'=>'1','default_role'=>'student']);
        if(releaseScalar($web,"SELECT setting_value FROM settings WHERE setting_key='site_tagline'")!=='Release verification')throw new RuntimeException('Settings not saved.');
        releasePost($web,'student','auth/profile',['full_name'=>'Release Student','email'=>'student@example.test','bio'=>'Synthetic profile']);
        if(releaseScalar($web,'SELECT full_name FROM users WHERE id=3')!=='Release Student')throw new RuntimeException('Profile not saved.');
        releasePost($web,'student','auth/setTheme',['theme'=>'dark']);
        if(releaseScalar($web,'SELECT theme_preference FROM users WHERE id=3')!=='dark')throw new RuntimeException('Theme not saved.');
        $start=date('Y-m-d').'T12:00';
        releasePost($web,'student','calendar/create',['title'=>'Release reminder','start_at'=>$start,'event_type'=>'reminder']);
        $calendar=$web->request('student','calendar');WebFixture::pageOk($calendar,'Personal calendar');
        if(!str_contains($calendar['body'],'Release reminder'))throw new RuntimeException('Calendar event absent.');
        $event=releaseScalar($web,"SELECT id FROM calendar_events WHERE title='Release reminder'");
        releasePost($web,'student','calendar/delete/'.$event,[]);
        if(releaseScalar($web,'SELECT COUNT(*) FROM calendar_events WHERE id='.$event)!=='0')throw new RuntimeException('Calendar event not deleted.');
        releasePost($web,'admin','admin/deleteCategory/'.$category,[]);
        releasePost($web,'admin','admin/deleteUser/'.$user,[]);
        if(releaseScalar($web,'SELECT COUNT(*) FROM users WHERE id='.$user)!=='0')throw new RuntimeException('Disposable user not deleted.');
    },
    'student can leave and rejoin a course on the documented SQLite runtime' => function():void {
        $web=new WebFixture();$web->login('student');
        $left=$web->request('student','course/unenroll/1');
        if($left['status']!==302)throw new RuntimeException('SQLite unenrollment failed.');
        $course=$web->request('student','course/view/1');WebFixture::pageOk($course,'Unenrolled course');
        $joined=$web->request('student','course/enroll/1',['_csrf_token'=>WebFixture::csrf($course)]);
        if($joined['status']!==302)throw new RuntimeException('Re-enrollment failed.');
        WebFixture::pageOk($web->request('student','course/learn/1'),'Rejoined course');
    },
    'announcement notifications respect role audience and course membership' => function():void {
        $web=new WebFixture();$web->login('admin');
        $form=$web->request('admin','admin/announcements');
        $sent=$web->request('admin','admin/announcements',['_csrf_token'=>WebFixture::csrf($form),'title'=>'Private student notice','body'=>'Synthetic student-only body','audience'=>'students','course_id'=>1]);
        if($sent['status']!==302)throw new RuntimeException('Student announcement rejected.');
        [$code,$output]=$web->php(['-r',"require 'config/config.php';require 'config/database.php';echo json_encode(Database::fetchAll(\"SELECT user_id FROM notifications WHERE title='Private student notice' ORDER BY user_id\"));"]);
        if($code!==0||json_decode($output,true)!==[['user_id'=>3]])throw new RuntimeException('Course student audience was not enforced.');
        $sent=$web->request('admin','admin/announcements',['_csrf_token'=>WebFixture::csrf($form),'title'=>'Private teacher notice','body'=>'Synthetic teacher-only body','audience'=>'teachers','course_id'=>1]);
        if($sent['status']!==302)throw new RuntimeException('Teacher announcement rejected.');
        [$code,$output]=$web->php(['-r',"require 'config/config.php';require 'config/database.php';echo json_encode(Database::fetchAll(\"SELECT user_id FROM notifications WHERE title='Private teacher notice' ORDER BY user_id\"));"]);
        if($code!==0||json_decode($output,true)!==[['user_id'=>2]])throw new RuntimeException('Course teacher audience was not enforced.');
        foreach(['teacher','student'] as $role){
            $web->login($role);$course=$web->request($role,'course/view/1');WebFixture::pageOk($course,'Announcement audience '.$role);
            $hidden=$role==='teacher'?'Private student notice':'Private teacher notice';
            if(str_contains($course['body'],$hidden))throw new RuntimeException('Course page discloses another role announcement.');
        }
    },
]];
