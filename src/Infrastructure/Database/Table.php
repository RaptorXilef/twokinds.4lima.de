<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

/**
 * Single Source of Truth für alle Datenbank-Tabellennamen.
 * Verhindert "Magic Strings" und ermöglicht Auto-Complete.
 */
final class Table
{
    public const ROLES            = 'roles';
    public const USERS            = 'users';
    public const USER_BOOKMARKS   = 'user_bookmarks';
    public const LOGIN_ATTEMPTS   = 'login_attempts';
    public const CHAPTERS         = 'chapters';
    public const COMICS           = 'comics';
    public const COMIC_REVISIONS  = 'comic_revisions';
    public const CHARACTERS       = 'characters';
    public const CHARACTER_GROUPS = 'character_groups';
    public const REPORTS          = 'reports';
    public const MAGIC_LINKS      = 'magic_links';
    public const MAIL_QUEUE       = 'mail_queue';
    public const MAIL_LOGS        = 'mail_logs';
}
