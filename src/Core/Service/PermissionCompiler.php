<?php

declare(strict_types=1);

namespace App\Core\Service;

final class PermissionCompiler
{
    public function compile(array $structure, array $groupPermissions): array
    {
        $flat = [];
        $this->walk($structure, $groupPermissions, true, $flat);

        return $flat;
    }

    private function walk(array $nodes, array $groupPerms, bool $parentAllowed, array &$result): void
    {
        foreach ($nodes as $node) {
            $key = $node['key'] ?? null;
            if ($key !== null) {
                $explicitAllow = \in_array($key, $groupPerms, true) || \in_array('*', $groupPerms, true);
                $explicitDeny  = \in_array('-' . $key, $groupPerms, true);
                $isAllowed     = $parentAllowed && $explicitAllow && ! $explicitDeny;
                $result[$key]  = $isAllowed;
            } else {
                $isAllowed = $parentAllowed;
            }

            if (! isset($node['children'])) {
                continue;
            }
            $this->walk($node['children'], $groupPerms, $isAllowed, $result);
        }
    }
}
