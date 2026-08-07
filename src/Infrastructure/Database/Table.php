<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

/**
 * Single Source of Truth für alle Datenbank-Tabellennamen.
 * Verhindert "Magic Strings" und ermöglicht Auto-Complete.
 */
final class Table
{
    public const string ROLES            = 'roles';
    public const string USERS            = 'users';
    public const string USER_BOOKMARKS   = 'user_bookmarks';
    public const string LOGIN_ATTEMPTS   = 'login_attempts';
    public const string CHAPTERS         = 'chapters';
    public const string COMICS           = 'comics';
    public const string COMIC_REVISIONS  = 'comic_revisions';
    public const string CHARACTERS       = 'characters';
    public const string CHARACTER_GROUPS = 'character_groups';
    public const string REPORTS          = 'reports';
    public const string MAGIC_LINKS      = 'magic_links';
    public const string MAIL_QUEUE       = 'mail_queue';
    public const string MAIL_LOGS        = 'mail_logs';
}
