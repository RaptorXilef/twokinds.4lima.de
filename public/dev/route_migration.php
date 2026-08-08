<?php

declare(strict_types=1);

// https://twokinds.../dev/route_migration.php

$actionsDir = \dirname(__DIR__, 2) . '/src/Application/Actions';

$map = [
    // Admin Views
    'AdminDashboardRenderAction.php' => ['dir' => 'Admin', 'class' => 'DashboardAction', 'auth' => true, 'routes' => [['GET', '/admin']]],
    'AdminLoginRenderAction.php' => ['dir' => 'Admin', 'class' => 'LoginAction', 'auth' => false, 'routes' => [['GET', '/admin/login']]],

    // API Admin (Auth always true except login)
    'ApiAdminLoginAction.php' => ['dir' => 'Api/Admin', 'class' => 'LoginAction', 'auth' => false, 'routes' => [['POST', '/api/admin_login']]],
    'ApiAdminLogoutAction.php' => ['dir' => 'Api/Admin', 'class' => 'LogoutAction', 'auth' => true, 'routes' => [['POST', '/api/admin_logout']]],
    'ApiAdminTriggerNewsletterAction.php' => ['dir' => 'Api/Admin', 'class' => 'TriggerNewsletterAction', 'auth' => true, 'routes' => [['POST', '/api/admin_trigger_newsletter']]],
    'ApiCreateBackupAction.php' => ['dir' => 'Api/Admin', 'class' => 'CreateBackupAction', 'auth' => true, 'routes' => [['POST', '/api/create_backup']]],
    'ApiCropSocialMediaAction.php' => ['dir' => 'Api/Admin', 'class' => 'CropSocialMediaAction', 'auth' => true, 'routes' => [['POST', '/api/crop_social_media']]],
    'ApiDeleteBackupAction.php' => ['dir' => 'Api/Admin', 'class' => 'DeleteBackupAction', 'auth' => true, 'routes' => [['POST', '/api/delete_backup']]],
    'ApiDeleteChapterAction.php' => ['dir' => 'Api/Admin', 'class' => 'DeleteChapterAction', 'auth' => true, 'routes' => [['POST', '/api/delete_chapter']]],
    'ApiDeleteCharacterAction.php' => ['dir' => 'Api/Admin', 'class' => 'DeleteCharacterAction', 'auth' => true, 'routes' => [['POST', '/api/delete_character']]],
    'ApiDeleteComicAction.php' => ['dir' => 'Api/Admin', 'class' => 'DeleteComicAction', 'auth' => true, 'routes' => [['POST', '/api/delete_comic']]],
    'ApiDeleteComicMediaAction.php' => ['dir' => 'Api/Admin', 'class' => 'DeleteComicMediaAction', 'auth' => true, 'routes' => [['POST', '/api/delete_comic_media']]],
    'ApiDeleteMediaAction.php' => ['dir' => 'Api/Admin', 'class' => 'DeleteMediaAction', 'auth' => true, 'routes' => [['POST', '/api/delete_media']]],
    'ApiDeleteRoleAction.php' => ['dir' => 'Api/Admin', 'class' => 'DeleteRoleAction', 'auth' => true, 'routes' => [['POST', '/api/delete_role']]],
    'ApiDeleteUserAction.php' => ['dir' => 'Api/Admin', 'class' => 'DeleteUserAction', 'auth' => true, 'routes' => [['POST', '/api/delete_user']]],
    'ApiDownloadBackupAction.php' => ['dir' => 'Api/Admin', 'class' => 'DownloadBackupAction', 'auth' => true, 'routes' => [['GET', '/api/download_backup']]],
    'ApiListBackupsAction.php' => ['dir' => 'Api/Admin', 'class' => 'ListBackupsAction', 'auth' => true, 'routes' => [['GET', '/api/list_backups']]],
    'ApiListComicMediaAction.php' => ['dir' => 'Api/Admin', 'class' => 'ListComicMediaAction', 'auth' => true, 'routes' => [['GET', '/api/list_comic_media']]],
    'ApiMediaListAction.php' => ['dir' => 'Api/Admin', 'class' => 'MediaListAction', 'auth' => true, 'routes' => [['GET', '/api/list_media']]],
    'ApiRestoreBackupAction.php' => ['dir' => 'Api/Admin', 'class' => 'RestoreBackupAction', 'auth' => true, 'routes' => [['POST', '/api/restore_backup']]],
    'ApiRestoreDeletedComicAction.php' => ['dir' => 'Api/Admin', 'class' => 'RestoreDeletedComicAction', 'auth' => true, 'routes' => [['POST', '/api/restore_deleted_comic']]],
    'ApiSaveChapterAction.php' => ['dir' => 'Api/Admin', 'class' => 'SaveChapterAction', 'auth' => true, 'routes' => [['POST', '/api/save_chapter']]],
    'ApiSaveCharacterGroupsAction.php' => ['dir' => 'Api/Admin', 'class' => 'SaveCharacterGroupsAction', 'auth' => true, 'routes' => [['POST', '/api/save_character_groups']]],
    'ApiSaveRoleAction.php' => ['dir' => 'Api/Admin', 'class' => 'SaveRoleAction', 'auth' => true, 'routes' => [['POST', '/api/save_role']]],
    'ApiSaveSingleCharacterAction.php' => ['dir' => 'Api/Admin', 'class' => 'SaveSingleCharacterAction', 'auth' => true, 'routes' => [['POST', '/api/save_single_character']]],
    'ApiSaveSingleComicAction.php' => ['dir' => 'Api/Admin', 'class' => 'SaveSingleComicAction', 'auth' => true, 'routes' => [['POST', '/api/save_single_comic']]],
    'ApiSaveUserAction.php' => ['dir' => 'Api/Admin', 'class' => 'SaveUserAction', 'auth' => true, 'routes' => [['POST', '/api/save_user']]],
    'ApiUndoComicAction.php' => ['dir' => 'Api/Admin', 'class' => 'UndoComicAction', 'auth' => true, 'routes' => [['POST', '/api/undo_comic']]],
    'ApiUpdateReportStatusAction.php' => ['dir' => 'Api/Admin', 'class' => 'UpdateReportStatusAction', 'auth' => true, 'routes' => [['POST', '/api/update_report_status']]],
    'ApiUploadComicMediaAction.php' => ['dir' => 'Api/Admin', 'class' => 'UploadComicMediaAction', 'auth' => true, 'routes' => [['POST', '/api/upload_comic_media']]],
    'ApiUploadMediaAction.php' => ['dir' => 'Api/Admin', 'class' => 'UploadMediaAction', 'auth' => true, 'routes' => [['POST', '/api/upload_media']]],

    // API Frontend
    'ApiFrontendDeleteAccountAction.php' => ['dir' => 'Api/Frontend', 'class' => 'DeleteAccountAction', 'auth' => true, 'routes' => [['POST', '/api/frontend_delete_account']]],
    'ApiFrontendForgotPasswordAction.php' => ['dir' => 'Api/Frontend', 'class' => 'ForgotPasswordAction', 'auth' => false, 'routes' => [['POST', '/api/frontend_forgot_password']]],
    'ApiFrontendLogoutAction.php' => ['dir' => 'Api/Frontend', 'class' => 'LogoutAction', 'auth' => false, 'routes' => [['POST', '/api/frontend_logout']]],
    'ApiFrontendRegisterAction.php' => ['dir' => 'Api/Frontend', 'class' => 'RegisterAction', 'auth' => false, 'routes' => [['POST', '/api/frontend_register']]],
    'ApiFrontendResendVerificationAction.php' => ['dir' => 'Api/Frontend', 'class' => 'ResendVerificationAction', 'auth' => false, 'routes' => [['POST', '/api/frontend_resend_verification']]],
    'ApiFrontendResetPasswordAction.php' => ['dir' => 'Api/Frontend', 'class' => 'ResetPasswordAction', 'auth' => false, 'routes' => [['POST', '/api/frontend_reset_password']]],
    'ApiFrontendUpdateProfileAction.php' => ['dir' => 'Api/Frontend', 'class' => 'UpdateProfileAction', 'auth' => true, 'routes' => [['POST', '/api/frontend_update_profile']]],
    'ApiSubmitReportAction.php' => ['dir' => 'Api/Frontend', 'class' => 'SubmitReportAction', 'auth' => false, 'routes' => [['POST', '/api/submit_report']]],
    'ApiSyncBookmarksAction.php' => ['dir' => 'Api/Frontend', 'class' => 'SyncBookmarksAction', 'auth' => false, 'routes' => [['POST', '/api/sync_bookmarks']]],
    'ApiToggleBookmarkAction.php' => ['dir' => 'Api/Frontend', 'class' => 'ToggleBookmarkAction', 'auth' => false, 'routes' => [['POST', '/api/toggle_bookmark']]],

    // API System & Shared
    'ApiCronBackupAction.php' => ['dir' => 'Api/System', 'class' => 'CronBackupAction', 'auth' => false, 'routes' => [['GET', '/api/cron_backup']]],
    'SystemProcessMailQueueAction.php' => ['dir' => 'Api/System', 'class' => 'ProcessMailQueueAction', 'auth' => false, 'routes' => [['GET', '/api/process_mail_queue']]],
    'ApiKeepAliveAction.php' => ['dir' => 'Api/Shared', 'class' => 'KeepAliveAction', 'auth' => false, 'routes' => [['GET', '/api/keep_alive']]],
    'ApiGetComicAction.php' => ['dir' => 'Api/Shared', 'class' => 'GetComicAction', 'auth' => true, 'routes' => [['GET', '/api/get_comic']]],
    'ApiGetTranscriptAction.php' => ['dir' => 'Api/Shared', 'class' => 'GetTranscriptAction', 'auth' => false, 'routes' => [['GET', '/api/get_transcript']]],

    // Frontend HTML Views (General)
    'ImprintAction.php' => ['dir' => 'Frontend', 'class' => 'ImprintAction', 'auth' => false, 'routes' => [['GET', '/impressum']]],
    'PrivacyAction.php' => ['dir' => 'Frontend', 'class' => 'PrivacyAction', 'auth' => false, 'routes' => [['GET', '/datenschutz']]],
    'ProjectInfoAction.php' => ['dir' => 'Frontend', 'class' => 'ProjectInfoAction', 'auth' => false, 'routes' => [['GET', '/projekt']]],

    // Frontend HTML Views (Subfolder Frontend/)
    'Frontend/FrontendForgotPasswordRenderAction.php' => ['dir' => 'Frontend', 'class' => 'ForgotPasswordAction', 'auth' => false, 'routes' => [['GET', '/passwort-vergessen']]],
    'Frontend/FrontendLoginRenderAction.php' => ['dir' => 'Frontend', 'class' => 'LoginAction', 'auth' => false, 'routes' => [['GET', '/login']]],
    'Frontend/FrontendProfileRenderAction.php' => ['dir' => 'Frontend', 'class' => 'ProfileAction', 'auth' => true, 'routes' => [['GET', '/profil']]],
    'Frontend/FrontendRegisterRenderAction.php' => ['dir' => 'Frontend', 'class' => 'RegisterAction', 'auth' => false, 'routes' => [['GET', '/registrieren']]],
    'Frontend/FrontendResendVerificationRenderAction.php' => ['dir' => 'Frontend', 'class' => 'ResendVerificationAction', 'auth' => false, 'routes' => [['GET', '/bestaetigungsmail-anfordern']]],
    'Frontend/FrontendResetPasswordRenderAction.php' => ['dir' => 'Frontend', 'class' => 'ResetPasswordAction', 'auth' => false, 'routes' => [['GET', '/passwort-reset']]],
    'Frontend/FrontendVerifyNewEmailRenderAction.php' => ['dir' => 'Frontend', 'class' => 'VerifyNewEmailAction', 'auth' => false, 'routes' => [['GET', '/email-bestaetigen']]],
    'Frontend/FrontendVerifyRenderAction.php' => ['dir' => 'Frontend', 'class' => 'VerifyAction', 'auth' => false, 'routes' => [['GET', '/verifizieren']]],
    'Frontend/RenderArchiveAction.php' => ['dir' => 'Frontend', 'class' => 'ArchiveAction', 'auth' => false, 'routes' => [['GET', '/archiv']]],
    'Frontend/RenderBookmarksAction.php' => ['dir' => 'Frontend', 'class' => 'BookmarksAction', 'auth' => false, 'routes' => [['GET', '/lesezeichen']]],
    'Frontend/RenderCharacterDetailAction.php' => ['dir' => 'Frontend', 'class' => 'CharacterDetailAction', 'auth' => false, 'routes' => [['GET', '/charaktere/{id}']]],
    'Frontend/RenderCharacterListAction.php' => ['dir' => 'Frontend', 'class' => 'CharacterListAction', 'auth' => false, 'routes' => [['GET', '/charaktere']]],
    'Frontend/RenderComicAction.php' => ['dir' => 'Frontend', 'class' => 'ComicAction', 'auth' => false, 'routes' => [['GET', '/'], ['GET', '/comic'], ['GET', '/comic/{id}']]],
    'Frontend/RenderError403Action.php' => ['dir' => 'Frontend', 'class' => 'Error403Action', 'auth' => false, 'routes' => [['GET', '/403']]],
    'Frontend/RenderError404Action.php' => ['dir' => 'Frontend', 'class' => 'Error404Action', 'auth' => false, 'routes' => [['GET', '/404']]],
];

echo "<pre>Starte Architektur-Migration...\n\n";

foreach ($map as $oldPath => $config) {
    $fullOldPath = $actionsDir . '/' . $oldPath;
    if (!\file_exists($fullOldPath)) {
        echo "Übersprungen: $oldPath existiert nicht (mehr).\n";

        continue;
    }

    $content = \file_get_contents($fullOldPath);

    // Namespace anpassen
    $newNamespace = 'App\\Application\\Actions\\' . \str_replace('/', '\\', $config['dir']);
    $content = \preg_replace('/namespace App\\\\Application\\\\Actions(?:\\\\[A-Za-z0-9_]+)*;/', "namespace $newNamespace;", $content);

    // Use Statements für neue Attribute hinzufügen (falls noch nicht da)
    if (!\str_contains($content, 'use App\Application\Attribute\Route;')) {
        $content = \preg_replace('/(namespace .*;)/', "$1\n\nuse App\\Application\\Attribute\\Route;", $content);
    }
    if ($config['auth'] && !\str_contains($content, 'use App\Application\Attribute\RequiresAuth;')) {
        $content = \preg_replace('/(use App\\\\Application\\\\Attribute\\\\Route;)/', "$1\nuse App\\Application\\Attribute\\RequiresAuth;", $content);
    }

    // Altes #[ActionRoute] entfernen und neues #[Route] generieren
    $routesStr = '';
    foreach ($config['routes'] as $r) {
        $routesStr .= "#[Route('{$r[0]}', '{$r[1]}')]\n";
    }
    if ($config['auth']) {
        $routesStr .= "#[RequiresAuth]\n";
    }

    // Ersetze altes ActionRoute mit neuen Routen
    $content = \preg_replace('/#\[ActionRoute\([^\)]+\)\]\s*/', $routesStr, $content);

    // Klassennamen umbenennen
    $oldClassPattern = \pathinfo($oldPath, \PATHINFO_FILENAME);
    $content = \preg_replace("/class $oldClassPattern/i", "class {$config['class']}", $content);

    // Neuen Pfad erstellen
    $newDir = $actionsDir . '/' . $config['dir'];
    if (!\is_dir($newDir)) {
        \mkdir($newDir, 0o755, true);
    }
    $newFullPath = $newDir . '/' . $config['class'] . '.php';

    \file_put_contents($newFullPath, $content);
    \unlink($fullOldPath);

    echo "Verschoben & Refactored: $oldPath -> {$config['dir']}/{$config['class']}.php\n";
}

// Lösche den Frontend Ordner falls er leer ist
if (\is_dir($actionsDir . '/Frontend')) {
    @\rmdir($actionsDir . '/Frontend');
}

echo "\nMigration abgeschlossen! Vergiss nicht, die Datei cache/routes_v2.php zu löschen, falls vorhanden.</pre>";
