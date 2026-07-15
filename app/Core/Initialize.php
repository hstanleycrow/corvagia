<?php

declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;
use App\Core\Http\Cors;
use App\Core\Config\EnvValidator;

class Initialize
{
    private const ROOT = __DIR__ . '/../..';

    private const REQUIRED_ENV = [
        'ENVIRONMENT',
        'AUTH_SALT',
        'RESORUCES_URL',
        'BUSINESS_NAME',
        'REPORT_ERROR_EMAIL',
        'DATABASE_HOST',
        'DATABASE_NAME',
        'DATABASE_USERNAME',
        'DATABASE_PORT',
        'DATABASE_CHARSET',
    ];

    private static bool $mapRoutes = true;
    private Dotenv $dotEnv;

    function __construct()
    {
        $this->init();
    }

    private function init(): void
    {
        $this->sessionStart();
        $this->callAutoload();
        $this->initDotEnv();
        EnvValidator::validate($_ENV, self::REQUIRED_ENV);
        $this->loadConfig();
        if (self::$mapRoutes) {
            $this->handleCors();
            $this->mapRoutes();
        }
    }

    /**
     * Applies the CORS policy to /api/ requests before routing: a preflight
     * OPTIONS is answered here (the router has no OPTIONS routes), and the real
     * request gets the headers echoed on its response.
     */
    private function handleCors(): void
    {
        $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
        if (!Cors::applies($path)) {
            return;
        }

        $origin = Cors::resolveOrigin(
            $_SERVER['HTTP_ORIGIN'] ?? null,
            Cors::allowedOrigins($_ENV)
        );

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            Cors::preflight($origin)->send();
            exit;
        }

        foreach (Cors::headers($origin) as $name => $value) {
            header("{$name}: {$value}");
        }
    }

    private function sessionStart(): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            $eightHours = 8 * 60 * 60; // 28800 seconds
            ini_set('session.gc_maxlifetime', $eightHours);
            session_set_cookie_params([
                'lifetime' => $eightHours,
                'path'     => '/',
                'secure'   => false, // set true if using HTTPS
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    private function callAutoload(): void
    {
        require_once self::ROOT . '/vendor/autoload.php';
    }

    private function initDotEnv(): void
    {
        $this->dotEnv = Dotenv::createImmutable(self::ROOT);
        $this->dotEnv->load();
    }

    private function mapRoutes(): void
    {
        // Admin and API first; web.php (with its catch-all) stays last.
        require_once self::ROOT . '/routes/admin.php';
        require_once self::ROOT . '/routes/api.php';
        require_once self::ROOT . '/routes/web.php';

        Route::dispatch();
    }

    private function loadConfig(): void
    {
        $file = self::ROOT . '/config/App.php';
        if (!is_file($file)) {
            die(sprintf('El archivo %s no se encuentra, y es requerido para el funcionamiento del sistema', $file));
        }

        require_once $file;
    }

    public static function start(bool $mapRoutes = true): static
    {
        self::$mapRoutes = $mapRoutes;

        return new self();
    }
}
