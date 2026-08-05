<?php

declare(strict_types=1);

return [
    'exact' => [
        'GET' => [
            '/admin' => [
                'class' => 'App\\Application\\Actions\\Admin\\DashboardAction',
                'auth'  => true,
            ],
            '/admin/login' => [
                'class' => 'App\\Application\\Actions\\Admin\\LoginAction',
                'auth'  => false,
            ],
            '/api/download_backup' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\DownloadBackupAction',
                'auth'  => true,
            ],
            '/api/list_backups' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\ListBackupsAction',
                'auth'  => true,
            ],
            '/api/list_comic_media' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\ListComicMediaAction',
                'auth'  => true,
            ],
            '/api/list_media' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\MediaListAction',
                'auth'  => true,
            ],
            '/api/get_comic' => [
                'class' => 'App\\Application\\Actions\\Api\\Shared\\GetComicAction',
                'auth'  => true,
            ],
            '/api/get_transcript' => [
                'class' => 'App\\Application\\Actions\\Api\\Shared\\GetTranscriptAction',
                'auth'  => false,
            ],
            '/api/keep_alive' => [
                'class' => 'App\\Application\\Actions\\Api\\Shared\\KeepAliveAction',
                'auth'  => false,
            ],
            '/api/cron_backup' => [
                'class' => 'App\\Application\\Actions\\Api\\System\\CronBackupAction',
                'auth'  => false,
            ],
            '/api/process_mail_queue' => [
                'class' => 'App\\Application\\Actions\\Api\\System\\ProcessMailQueueAction',
                'auth'  => false,
            ],
            '/archiv' => [
                'class' => 'App\\Application\\Actions\\Frontend\\ArchiveAction',
                'auth'  => false,
            ],
            '/lesezeichen' => [
                'class' => 'App\\Application\\Actions\\Frontend\\BookmarksAction',
                'auth'  => false,
            ],
            '/charaktere' => [
                'class' => 'App\\Application\\Actions\\Frontend\\CharacterListAction',
                'auth'  => false,
            ],
            '/' => [
                'class' => 'App\\Application\\Actions\\Frontend\\ComicAction',
                'auth'  => false,
            ],
            '/comic' => [
                'class' => 'App\\Application\\Actions\\Frontend\\ComicAction',
                'auth'  => false,
            ],
            '/403' => [
                'class' => 'App\\Application\\Actions\\Frontend\\Error403Action',
                'auth'  => false,
            ],
            '/404' => [
                'class' => 'App\\Application\\Actions\\Frontend\\Error404Action',
                'auth'  => false,
            ],
            '/passwort-vergessen' => [
                'class' => 'App\\Application\\Actions\\Frontend\\ForgotPasswordAction',
                'auth'  => false,
            ],
            '/impressum' => [
                'class' => 'App\\Application\\Actions\\Frontend\\ImprintAction',
                'auth'  => false,
            ],
            '/login' => [
                'class' => 'App\\Application\\Actions\\Frontend\\LoginAction',
                'auth'  => false,
            ],
            '/datenschutz' => [
                'class' => 'App\\Application\\Actions\\Frontend\\PrivacyAction',
                'auth'  => false,
            ],
            '/profil' => [
                'class' => 'App\\Application\\Actions\\Frontend\\ProfileAction',
                'auth'  => true,
            ],
            '/projekt' => [
                'class' => 'App\\Application\\Actions\\Frontend\\ProjectInfoAction',
                'auth'  => false,
            ],
            '/registrieren' => [
                'class' => 'App\\Application\\Actions\\Frontend\\RegisterAction',
                'auth'  => false,
            ],
            '/bestaetigungsmail-anfordern' => [
                'class' => 'App\\Application\\Actions\\Frontend\\ResendVerificationAction',
                'auth'  => false,
            ],
            '/passwort-reset' => [
                'class' => 'App\\Application\\Actions\\Frontend\\ResetPasswordAction',
                'auth'  => false,
            ],
            '/verifizieren' => [
                'class' => 'App\\Application\\Actions\\Frontend\\VerifyAction',
                'auth'  => false,
            ],
            '/email-bestaetigen' => [
                'class' => 'App\\Application\\Actions\\Frontend\\VerifyNewEmailAction',
                'auth'  => false,
            ],
        ],
        'POST' => [
            '/api/create_backup' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\CreateBackupAction',
                'auth'  => true,
            ],
            '/api/crop_social_media' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\CropSocialMediaAction',
                'auth'  => true,
            ],
            '/api/delete_backup' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\DeleteBackupAction',
                'auth'  => true,
            ],
            '/api/delete_chapter' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\DeleteChapterAction',
                'auth'  => true,
            ],
            '/api/delete_character' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\DeleteCharacterAction',
                'auth'  => true,
            ],
            '/api/delete_comic' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\DeleteComicAction',
                'auth'  => true,
            ],
            '/api/delete_comic_media' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\DeleteComicMediaAction',
                'auth'  => true,
            ],
            '/api/delete_media' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\DeleteMediaAction',
                'auth'  => true,
            ],
            '/api/delete_role' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\DeleteRoleAction',
                'auth'  => true,
            ],
            '/api/delete_user' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\DeleteUserAction',
                'auth'  => true,
            ],
            '/api/admin_login' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\LoginAction',
                'auth'  => false,
            ],
            '/api/admin_logout' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\LogoutAction',
                'auth'  => true,
            ],
            '/api/restore_backup' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\RestoreBackupAction',
                'auth'  => true,
            ],
            '/api/restore_deleted_comic' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\RestoreDeletedComicAction',
                'auth'  => true,
            ],
            '/api/save_chapter' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\SaveChapterAction',
                'auth'  => true,
            ],
            '/api/save_character_groups' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\SaveCharacterGroupsAction',
                'auth'  => true,
            ],
            '/api/save_role' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\SaveRoleAction',
                'auth'  => true,
            ],
            '/api/save_single_character' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\SaveSingleCharacterAction',
                'auth'  => true,
            ],
            '/api/save_single_comic' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\SaveSingleComicAction',
                'auth'  => true,
            ],
            '/api/save_user' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\SaveUserAction',
                'auth'  => true,
            ],
            '/api/admin_trigger_newsletter' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\TriggerNewsletterAction',
                'auth'  => true,
            ],
            '/api/undo_comic' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\UndoComicAction',
                'auth'  => true,
            ],
            '/api/update_report_status' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\UpdateReportStatusAction',
                'auth'  => true,
            ],
            '/api/upload_comic_media' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\UploadComicMediaAction',
                'auth'  => true,
            ],
            '/api/upload_media' => [
                'class' => 'App\\Application\\Actions\\Api\\Admin\\UploadMediaAction',
                'auth'  => true,
            ],
            '/api/frontend_delete_account' => [
                'class' => 'App\\Application\\Actions\\Api\\Frontend\\DeleteAccountAction',
                'auth'  => true,
            ],
            '/api/frontend_forgot_password' => [
                'class' => 'App\\Application\\Actions\\Api\\Frontend\\ForgotPasswordAction',
                'auth'  => false,
            ],
            '/api/frontend_logout' => [
                'class' => 'App\\Application\\Actions\\Api\\Frontend\\LogoutAction',
                'auth'  => false,
            ],
            '/api/frontend_register' => [
                'class' => 'App\\Application\\Actions\\Api\\Frontend\\RegisterAction',
                'auth'  => false,
            ],
            '/api/frontend_resend_verification' => [
                'class' => 'App\\Application\\Actions\\Api\\Frontend\\ResendVerificationAction',
                'auth'  => false,
            ],
            '/api/frontend_reset_password' => [
                'class' => 'App\\Application\\Actions\\Api\\Frontend\\ResetPasswordAction',
                'auth'  => false,
            ],
            '/api/submit_report' => [
                'class' => 'App\\Application\\Actions\\Api\\Frontend\\SubmitReportAction',
                'auth'  => false,
            ],
            '/api/sync_bookmarks' => [
                'class' => 'App\\Application\\Actions\\Api\\Frontend\\SyncBookmarksAction',
                'auth'  => false,
            ],
            '/api/toggle_bookmark' => [
                'class' => 'App\\Application\\Actions\\Api\\Frontend\\ToggleBookmarkAction',
                'auth'  => false,
            ],
            '/api/frontend_update_profile' => [
                'class' => 'App\\Application\\Actions\\Api\\Frontend\\UpdateProfileAction',
                'auth'  => true,
            ],
            '/api/upload_avatar' => [
                'class' => 'App\\Application\\Actions\\Api\\Frontend\\UploadAvatarAction',
                'auth'  => true,
            ],
        ],
    ],
    'dynamic' => [
        'GET' => [
            '#^/charaktere/(?P<id>[^/]+)$#' => [
                'class' => 'App\\Application\\Actions\\Frontend\\CharacterDetailAction',
                'auth'  => false,
            ],
            '#^/comic/(?P<id>[^/]+)$#' => [
                'class' => 'App\\Application\\Actions\\Frontend\\ComicAction',
                'auth'  => false,
            ],
        ],
    ],
];
