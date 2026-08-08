<?php

declare(strict_types=1);

namespace App\Core\Security;

/**
 * ! Rechte
 * ! Permissions
 * ! Rechtesystem
 */
final class PermissionRegistry
{
    /**
     * @return array<string, mixed>
     */
    public static function getStructure(): array
    {
        return [
            'admin' => [
                'label' => 'Admin-Bereich',
                'key' => 'admin.access',
                'children' => [
                    'dashboard' => ['label' => 'Dashboard betreten', 'key' => 'dashboard.view'],
                ],
            ],
            'comics' => [
                'label' => 'Comic-Verwaltung',
                'key' => 'comics.manage',
                'children' => [
                    'create_edit' => ['label' => 'Comics anlegen & bearbeiten', 'key' => 'comics.edit'],
                    'delete' => ['label' => 'Comics löschen & wiederherstellen', 'key' => 'comics.delete'],
                ],
            ],
            'characters' => [
                'label' => 'Charakter-Verwaltung',
                'key' => 'characters.manage',
                'children' => [
                    'create_edit' => ['label' => 'Charaktere anlegen & bearbeiten', 'key' => 'characters.edit'],
                    'delete' => ['label' => 'Charaktere löschen', 'key' => 'characters.delete'],
                    'groups' => ['label' => 'Charakter-Gruppen sortieren', 'key' => 'groups.manage'],
                ],
            ],
            'chapters' => [
                'label' => 'Archiv / Kapitel',
                'key' => 'chapters.manage',
                'children' => [
                    'create_edit' => ['label' => 'Kapitel anlegen & bearbeiten', 'key' => 'chapters.edit'],
                    'delete' => ['label' => 'Kapitel löschen', 'key' => 'chapters.delete'],
                ],
            ],
            'media' => [
                'label' => 'Galerie / Bilder',
                'key' => 'media.manage',
                'children' => [
                    'upload' => ['label' => 'Bilder hochladen', 'key' => 'media.upload'],
                    'delete' => ['label' => 'Bilder vom Server löschen', 'key' => 'media.delete'],
                ],
            ],
            'reports' => [
                'label' => 'Fehlerberichte (Reports)',
                'key' => 'reports.view',
                'children' => [
                    'resolve' => ['label' => 'Berichte schließen/lösen', 'key' => 'reports.resolve'],
                    'delete' => ['label' => 'Berichte als Spam markieren', 'key' => 'reports.delete'],
                ],
            ],
            'system' => [
                'label' => 'System & Einstellungen',
                'key' => 'system.manage',
                'children' => [
                    'users' => ['label' => 'Benutzer verwalten', 'key' => 'system.users.manage'],
                    'roles' => ['label' => 'Rechte-Gruppen (Rollen) verwalten', 'key' => 'system.roles.manage'],
                ],
            ],
            'backup' => [
                'label' => 'Backups & Migration',
                'key' => 'system.backup.manage',
            ],
        ];
    }
}
