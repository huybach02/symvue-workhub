<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class PermissionCheckerService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
    ) {}

    public function hasAccess(Request $request, array $permissions): bool
    {
        $path = str_replace('/api/', '', $request->getPathInfo());
        $method = $request->getMethod();
        $specialPermission = $this->resolveSpecialPermission($request, $path, $method);

        if ($specialPermission !== null) {
            return $this->matchesResolvedPermission($permissions, $specialPermission);
        }

        return $this->hasDefaultPermission($permissions, $path, $method);
    }

    private function hasDefaultPermission(
        array $permissions,
        string $path,
        string $method,
    ): bool {
        $action = convertMethod($path, $method);

        foreach ($permissions as $permission) {
            if (
                str_contains($path, (string) ($permission['name'] ?? ''))
                && (($permission['actions'][$action] ?? false) === true)
            ) {
                return true;
            }
        }

        return false;
    }

    private function resolveSpecialPermission(
        Request $request,
        string $path,
        string $method,
    ): ?array {
        foreach ($this->getSpecialRoutes() as $route) {
            if (
                !is_array($route) ||
                !is_string($route['pattern'] ?? null) ||
                preg_match($route['pattern'], $path, $matches) !== 1
            ) {
                continue;
            }

            $methods = $route['methods'] ?? [];
            if (is_array($methods) && $methods !== [] && !in_array($method, $methods, true)) {
                continue;
            }

            $action = $this->resolveSpecialAction($route, $method, $matches);
            if ($action === null) {
                continue;
            }

            $permissionConfig = $route['permission'] ?? null;
            if (!is_array($permissionConfig)) {
                return null;
            }

            return $this->buildResolvedPermission($request, $permissionConfig, $action);
        }

        return null;
    }

    private function resolveSpecialAction(
        array $route,
        string $method,
        array $matches,
    ): ?string {
        if (isset($route['action']) && is_string($route['action'])) {
            return $route['action'];
        }

        $actionMap = $route['action_map'] ?? null;
        if (is_array($actionMap) && is_string($actionMap[$method] ?? null)) {
            return $actionMap[$method];
        }

        $actionFromMatch = $route['action_from_match'] ?? null;
        if (is_int($actionFromMatch) && is_string($matches[$actionFromMatch] ?? null)) {
            return $matches[$actionFromMatch];
        }

        return null;
    }

    private function buildResolvedPermission(
        Request $request,
        array $permissionConfig,
        string $action,
    ): ?array {
        $type = (string) ($permissionConfig['type'] ?? 'static');

        if ($type === 'hierarchical') {
            $base = trim((string) ($permissionConfig['base'] ?? ''));
            if ($base === '') {
                return null;
            }

            return [
                'kind' => 'hierarchical',
                'action' => $action,
                'base' => $base,
                'suffix' => $this->resolveHierarchicalSuffix($request, $permissionConfig),
            ];
        }

        $name = trim((string) ($permissionConfig['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        return [
            'kind' => 'static',
            'action' => $action,
            'name' => $name,
        ];
    }

    private function resolveHierarchicalSuffix(
        Request $request,
        array $permissionConfig,
    ): ?string {
        $source = (string) ($permissionConfig['source'] ?? '');

        return match ($source) {
            'query' => normalizePermissionSegment(
                $request->query->get((string) ($permissionConfig['key'] ?? '')),
            ),
            'body' => normalizePermissionSegment(
                getRequestBodyValue($request, (string) ($permissionConfig['key'] ?? '')),
            ),
            'entity' => normalizePermissionSegment(
                $this->getEntityValue($request, $permissionConfig),
            ),
            default => null,
        };
    }

    private function getEntityValue(Request $request, array $permissionConfig): mixed
    {
        $entityClass = resolveEntityClass($permissionConfig['entity'] ?? null);
        $field = trim((string) ($permissionConfig['field'] ?? ''));
        $idParam = trim((string) ($permissionConfig['id_param'] ?? 'id'));

        if ($entityClass === null || $field === '' || $idParam === '') {
            return null;
        }

        $entityId = $request->attributes->get($idParam);
        if ($entityId === null || $entityId === '') {
            return null;
        }

        $entity = $this->entityManager->getRepository($entityClass)->find($entityId);
        if (!is_object($entity)) {
            return null;
        }

        return readObjectField($entity, $field);
    }

    private function matchesResolvedPermission(
        array $permissions,
        array $resolvedPermission,
    ): bool {
        $action = (string) ($resolvedPermission['action'] ?? '');
        if ($action === '') {
            return false;
        }

        $kind = (string) ($resolvedPermission['kind'] ?? 'static');

        if ($kind === 'hierarchical') {
            $base = trim((string) ($resolvedPermission['base'] ?? ''));
            $suffix = trim((string) ($resolvedPermission['suffix'] ?? ''));

            if ($base === '') {
                return false;
            }

            if ($suffix !== '') {
                return $this->hasExactPermission($permissions, $base . ':' . $suffix, $action);
            }

            return $this->hasExactPermission($permissions, $base, $action);
        }

        $name = trim((string) ($resolvedPermission['name'] ?? ''));
        if ($name === '') {
            return false;
        }

        return $this->hasExactPermission($permissions, $name, $action);
    }

    private function hasExactPermission(
        array $permissions,
        string $name,
        string $action,
    ): bool {
        foreach ($permissions as $permission) {
            if (
                ($permission['name'] ?? null) === $name
                && (($permission['actions'][$action] ?? false) === true)
            ) {
                return true;
            }
        }

        return false;
    }

    private function getSpecialRoutes(): array
    {
        $configPath = $this->projectDir . '/config/permission_special_routes.php';

        if (!file_exists($configPath)) {
            return [];
        }

        $routes = require $configPath;

        return is_array($routes) ? $routes : [];
    }
}
