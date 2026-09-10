<?php

namespace App\Services\Auth;

/**
 * Reads config/permission_matrix.php — single source of truth for web RBAC.
 */
class PermissionMatrix
{
    public static function modules(): array
    {
        $modules = config('permission_matrix.modules', []);
        uasort($modules, fn ($a, $b) => ($a['sort'] ?? 100) <=> ($b['sort'] ?? 100));

        return $modules;
    }

    public static function legacyMap(): array
    {
        return config('permission_matrix.legacy_map', []);
    }

    public static function expandMap(): array
    {
        return config('permission_matrix.expand_map', []);
    }

    public static function roleTemplates(): array
    {
        return config('permission_matrix.role_templates', []);
    }

    /**
     * Resolve any permission key (old or new) to the canonical matrix key.
     */
    public static function resolve(string $permissionKey): string
    {
        $map = self::legacyMap();

        return $map[$permissionKey] ?? $permissionKey;
    }

    /**
     * @return list<string>
     */
    public static function crudKeys(string $feature): array
    {
        return ["{$feature}_view", "{$feature}_add", "{$feature}_edit", "{$feature}_delete"];
    }

    /** Pipe string for middleware: view|add|edit|delete */
    public static function anyCrud(string $feature): string
    {
        return implode('|', self::crudKeys($feature));
    }

    /**
     * Home dashboard widget permission keys (Roles → Dashboard → Home widgets).
     *
     * @return list<string>
     */
    public static function dashboardWidgetKeys(): array
    {
        return [
            'dashboard_clinical',
            'dashboard_reception',
            'dashboard_revenue',
            'dashboard_ot',
            'dashboard_staff',
        ];
    }

    /**
     * All keys that satisfy a permission check.
     *
     * - Bundle/manage keys (expand_map sources): any of their CRUD targets count.
     * - Specific actions (*_view/_add/_edit/_delete): ONLY that action (no sibling/manage leak).
     *
     * @return list<string>
     */
    public static function aliasesFor(string $permissionKey): array
    {
        $resolved = self::resolve($permissionKey);
        $aliases = [$permissionKey, $resolved];
        $expand = self::expandMap();

        // Bundle/manage keys win even when legacy_map points at a *_view key
        foreach ([$permissionKey, $resolved] as $key) {
            if (isset($expand[$key])) {
                return array_values(array_unique(array_merge($aliases, $expand[$key])));
            }
        }

        $isSpecificAction = (bool) preg_match('/_(view|add|edit|delete)$/', $resolved);

        if ($isSpecificAction) {
            return array_values(array_unique($aliases));
        }

        return array_values(array_unique($aliases));
    }

    /**
     * Flatten matrix into DB-ready permission rows.
     *
     * @return list<array{module:string,action:string,label:string,description:string,sort_order:int}>
     */
    public static function flatten(): array
    {
        $rows = [];
        $order = 0;

        foreach (self::modules() as $moduleKey => $module) {
            $features = $module['features'] ?? [];
            uasort($features, fn ($a, $b) => ($a['sort'] ?? 100) <=> ($b['sort'] ?? 100));

            foreach ($features as $featureKey => $feature) {
                $actions = $feature['actions'] ?? [];
                usort($actions, fn ($a, $b) => ($a['sort'] ?? 100) <=> ($b['sort'] ?? 100));

                foreach ($actions as $action) {
                    $key = (string) ($action['key'] ?? '');
                    if ($key === '') {
                        continue;
                    }

                    $order += 10;
                    $featureLabel = $feature['label'] ?? $featureKey;
                    $actionLabel = $action['label'] ?? $key;

                    $rows[] = [
                        'module' => (string) $moduleKey,
                        'action' => $key,
                        'label' => $actionLabel,
                        'description' => trim(($module['label'] ?? $moduleKey).' / '.$featureLabel.' / '.$actionLabel),
                        'sort_order' => $order,
                        'feature' => (string) $featureKey,
                        'feature_label' => (string) $featureLabel,
                        'module_label' => (string) ($module['label'] ?? $moduleKey),
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    public static function allKeys(): array
    {
        return array_values(array_unique(array_column(self::flatten(), 'action')));
    }

    /**
     * Tree for Role UI (module → feature → actions), without DB ids.
     */
    public static function tree(): array
    {
        $tree = [];

        foreach (self::modules() as $moduleKey => $module) {
            $featuresOut = [];
            $features = $module['features'] ?? [];
            uasort($features, fn ($a, $b) => ($a['sort'] ?? 100) <=> ($b['sort'] ?? 100));

            foreach ($features as $featureKey => $feature) {
                $actions = $feature['actions'] ?? [];
                usort($actions, fn ($a, $b) => ($a['sort'] ?? 100) <=> ($b['sort'] ?? 100));

                $featuresOut[$featureKey] = [
                    'label' => $feature['label'] ?? $featureKey,
                    'actions' => array_values(array_map(static fn ($a) => [
                        'key' => $a['key'],
                        'label' => $a['label'] ?? $a['key'],
                    ], $actions)),
                ];
            }

            $tree[$moduleKey] = [
                'label' => $module['label'] ?? $moduleKey,
                'features' => $featuresOut,
            ];
        }

        return $tree;
    }
}
