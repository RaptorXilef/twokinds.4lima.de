<?php

declare(strict_types=1);

namespace App\Core\Security;

final class PermissionRegistry
{
    public static function getStructure(): array
    {
        return [
            'dashboard' => [
                'label' => 'Dashboard & Allgemein',
                'key'   => 'dashboard.view',
            ],
            'comics' => [
                'label'    => 'Comic-Verwaltung',
                'key'      => 'comics.manage',
                'children' => [
                    'create' => ['label' => 'Comics hinzufügen', 'key' => 'comics.create'],
                    'edit'   => ['label' => 'Comics bearbeiten', 'key' => 'comics.edit'],
                    'delete' => ['label' => 'Comics löschen', 'key' => 'comics.delete'],
                ],
            ],
            'characters' => [
                'label'    => 'Charakter-Verwaltung',
                'key'      => 'characters.manage',
                'children' => [
                    'create' => ['label' => 'Charaktere hinzufügen', 'key' => 'characters.create'],
                    'edit'   => ['label' => 'Charaktere bearbeiten', 'key' => 'characters.edit'],
                    'delete' => ['label' => 'Charaktere löschen', 'key' => 'characters.delete'],
                ],
            ],
            'reports' => [
                'label'    => 'Fehlerberichte (Reports)',
                'key'      => 'reports.view',
                'children' => [
                    'resolve' => ['label' => 'Berichte schließen/lösen', 'key' => 'reports.resolve'],
                    'delete'  => ['label' => 'Berichte als Spam markieren/löschen', 'key' => 'reports.delete'],
                ],
            ],
            'system' => [
                'label'    => 'System & Einstellungen',
                'key'      => 'system.manage',
                'children' => [
                    'users'  => ['label' => 'Benutzer verwalten', 'key' => 'system.users.manage'],
                    'groups' => ['label' => 'Rechte-Gruppen verwalten', 'key' => 'system.groups.manage'],
                    'cache'  => ['label' => 'Cache & Tools ausführen', 'key' => 'system.tools.execute'],
                ],
            ],
        ];
    }
}
