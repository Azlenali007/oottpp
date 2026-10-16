<?php
/**
 * View Template Renderer
 * 
 * Simple, fast template renderer for Core PHP.
 * Supports layouts, components, and automatic output escaping helpers.
 */

declare(strict_types=1);

namespace App\Core;

use Exception;

class View {
    private static string $layout = 'layouts/main';

    public static function setLayout(string $layout): void {
        self::$layout = $layout;
    }

    public static function render(string $viewPath, array $data = [], ?string $customLayout = null): string {
        $fullPath = VIEWS_PATH . '/' . trim($viewPath, '/') . '.php';
        if (!file_exists($fullPath)) {
            throw new Exception("View template [{$viewPath}] not found at {$fullPath}");
        }

        // Extract view data into local scope
        extract($data, EXTR_SKIP);

        // Capture view content
        ob_start();
        require $fullPath;
        $content = ob_get_clean();

        // Check if layout is disabled
        $activeLayout = $customLayout !== null ? $customLayout : self::$layout;
        if ($activeLayout === '' || $activeLayout === false) {
            return $content;
        }

        $layoutPath = VIEWS_PATH . '/' . trim($activeLayout, '/') . '.php';
        if (!file_exists($layoutPath)) {
            return $content;
        }

        // Render layout with $content
        ob_start();
        require $layoutPath;
        return ob_get_clean();
    }

    /**
     * Include a reusable view component
     */
    public static function component(string $componentName, array $props = []): void {
        $componentPath = VIEWS_PATH . '/components/' . trim($componentName, '/') . '.php';
        if (file_exists($componentPath)) {
            extract($props, EXTR_SKIP);
            require $componentPath;
        }
    }

    /**
     * Helper for XSS safe string output
     */
    public static function e(mixed $value): string {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
