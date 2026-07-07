<?php
if (!function_exists('is_admin_request')) {
    function is_admin_request(): bool
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $phpSelf = $_SERVER['PHP_SELF'] ?? '';

        return preg_match('#/admin(/|$)#', $script) === 1
            || preg_match('#/admin(/|$)#', $requestUri) === 1
            || preg_match('#/admin(/|$)#', $phpSelf) === 1;
    }
}

if (!function_exists('asset_url')) {
    function asset_url($path = ''): string
    {
        if (is_array($path)) {
            $path = $path[0] ?? '';
        } elseif (is_string($path)) {
            $decoded = json_decode($path, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && count($decoded)) {
                $path = $decoded[0] ?? '';
            }
        }

        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        $path = str_replace('\\', '/', $path);
        if (preg_match('#^https?://#i', $path) || str_starts_with($path, 'data:')) {
            return $path;
        }

        $path = ltrim($path, '/');
        $prefix = is_admin_request() ? '../' : './';

        if (preg_match('#^images/#', $path) || preg_match('#^\./images/#', $path)) {
            $normalized = preg_replace('#^\.?/images/#', 'images/', $path);
            return $prefix . $normalized;
        }

        if (preg_match('#^uploads/#', $path) || preg_match('#^\./uploads/#', $path)) {
            $normalized = preg_replace('#^\.?/uploads/#', 'uploads/', $path);
            return $prefix . $normalized;
        }

        if (preg_match('#^\.\./images/#', $path)) {
            return $prefix . preg_replace('#^\.\./images/#', 'images/', $path);
        }

        if (preg_match('#^\.\./uploads/#', $path)) {
            return $prefix . preg_replace('#^\.\./uploads/#', 'uploads/', $path);
        }

        return $path;
    }
}

if (!function_exists('normalize_image_paths')) {
    function normalize_image_paths($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, function ($item) {
                return is_string($item) && trim($item) !== '';
            }));
        }

        if (!is_string($value)) {
            return [];
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return [];
        }

        $decoded = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter($decoded, function ($item) {
                return is_string($item) && trim($item) !== '';
            }));
        }

        return [$trimmed];
    }
}

if (!function_exists('first_image_path')) {
    function first_image_path($value): ?string
    {
        $paths = normalize_image_paths($value);
        return $paths[0] ?? null;
    }
}

if (!function_exists('store_uploaded_assets')) {
    function store_uploaded_assets(array $files, string $prefix = 'asset'): array
    {
        $paths = [];
        if (empty($files['name'])) {
            return $paths;
        }

        $count = is_array($files['name']) ? count($files['name']) : 0;
        for ($i = 0; $i < $count; $i++) {
            $file = [
                'name' => $files['name'][$i] ?? '',
                'type' => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$i] ?? 0,
            ];

            $upload = store_uploaded_asset($file, $prefix);
            if ($upload['ok']) {
                $paths[] = $upload['path'];
            }
        }

        return $paths;
    }
}

if (!function_exists('asset_root_dir')) {
    function asset_root_dir(): string
    {
        return dirname(__DIR__) . '/uploads';
    }
}

if (!function_exists('store_uploaded_asset')) {
    function store_uploaded_asset(array $file, string $prefix = 'asset'): array
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        $tmpName = $file['tmp_name'] ?? '';

        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($tmpName)) {
            return ['ok' => false, 'path' => null, 'error' => 'No file uploaded.'];
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            return ['ok' => false, 'path' => null, 'error' => 'Unsupported image format.'];
        }

        $targetDir = asset_root_dir();
        if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
            return ['ok' => false, 'path' => null, 'error' => 'Unable to create upload folder.'];
        }

        $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            return ['ok' => false, 'path' => null, 'error' => 'File upload failed.'];
        }

        return ['ok' => true, 'path' => 'uploads/' . $filename, 'error' => null];
    }
}
