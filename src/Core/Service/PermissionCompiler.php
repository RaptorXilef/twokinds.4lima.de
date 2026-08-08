<?php

declare(strict_types=1);

namespace App\Core\Service;

final class PermissionCompiler
{
    /**
     * @param array<int|string, mixed> $structure
     * @param array<int, string>       $groupPermissions
     *
     * @return array<string, bool>
     */
    public function compile(array $structure, array $groupPermissions): array
    {
        /** @var array<string, bool> $flat */
        $flat = [];
        // Wir starten mit false (Default Deny)
        $this->walk($structure, $groupPermissions, false, $flat);

        return $flat;
    }

    /**
     * @param array<int|string, mixed> $nodes
     * @param array<int, string>       $groupPerms
     * @param array<string, bool>      &$result
     */
    private function walk(array $nodes, array $groupPerms, bool $parentAllowed, array &$result): void
    {
        foreach ($nodes as $node) {
            if (! \is_array($node)) {
                continue;
            }

            $key = isset($node['key']) && \is_string($node['key']) ? $node['key'] : null;

            if ($key !== null) {
                $explicitAllow = \in_array($key, $groupPerms, true) || \in_array('*', $groupPerms, true);
                $explicitDeny  = \in_array('-' . $key, $groupPerms, true);

                // LOGIK-FIX: Erlaubt, wenn der Parent erlaubt ist ODER es explizit erlaubt wurde,
                // ABER NICHT, wenn es durch ein '-' explizit verboten wurde.
                $isAllowed    = ($parentAllowed || $explicitAllow) && ! $explicitDeny;
                $result[$key] = $isAllowed;
            } else {
                $isAllowed = $parentAllowed;
            }

            if (! isset($node['children']) || ! \is_array($node['children'])) {
                continue;
            }

            /** @var array<int|string, mixed> $children */
            $children = $node['children'];
            $this->walk($children, $groupPerms, $isAllowed, $result);
        }
    }
}
