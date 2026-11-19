<?php
/** SRS cleanup action matrix: public actions only; role/object checks remain in controllers. */
return [
    'Scan' => ['status','history','rescan'],
    'SubmissionFile' => ['download'],
    'Home' => ['index'],
    'Auth' => ['login', 'register', 'logout', 'profile', 'settheme'],
    'Admin' => ['index', 'users', 'createuser', 'edituser', 'deleteuser', 'courses', 'categories', 'deletecategory', 'announcements', 'deleteannouncement', 'settings', 'importusers', 'exportusers'],
    'Teacher' => ['index', 'courses', 'createcourse', 'editcourse', 'addmodule', 'deletemodule', 'addlesson', 'deletelesson', 'assignment', 'createassignment', 'gradesubmission', 'gradebook', 'gradebookcourse'],
    'Student' => ['index', 'grades', 'submitassignment'],
    'Course' => ['view', 'enroll', 'unenroll', 'learn', 'completelesson', 'students'],
    'Catalog' => ['index'],
    'Assignment' => ['view'],
    'Calendar' => ['index', 'create', 'delete', 'ical'],
    'Notifications' => ['index', 'markallread', 'markread'],
    'Flashcard' => ['index', 'create', 'view', 'study', 'review', 'delete'],
    'Backup' => ['index', 'create', 'download', 'delete'],
];
