<?php

namespace Tuijncode\Version;

use Dotenv\Dotenv;
use Exception;
use PDO;
use PDOException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RequestHandler
{
    protected string $token;

    protected $pdo;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
        $dotenv->load();

        $this->token = isset($_ENV['TUIJNCODE_VERSION_TOKEN']) ? $_ENV['TUIJNCODE_VERSION_TOKEN'] : '';

        if (! empty($_ENV['TUIJNCODE_VERSION_PDO_DSN'])) {
            try {
                $this->pdo = new PDO($_ENV['TUIJNCODE_VERSION_PDO_DSN'], $_ENV['TUIJNCODE_VERSION_PDO_USERNAME'], $_ENV['TUIJNCODE_VERSION_PDO_PASSWORD']);
            } catch (PDOException $e) {
                exit('Connection failed: '.$e->getMessage());
            }
        }
    }

    /**
     * Handle Request.
     */
    public function handleRequest()
    {
        if (empty($this->token)) {
            return new JsonResponse([
                'status' => 'ERROR',
                'message' => 'Token Is Not Configured Yet.',
            ], 400);
        }

        $request = Request::createFromGlobals();
        $requestToken = $request->query->get('token');

        if (empty($requestToken)) {
            return new JsonResponse([
                'status' => 'ERROR',
                'message' => 'Token Is Required.',
            ], 400);
        }

        if ($requestToken !== $this->token) {
            return new JsonResponse([
                'status' => 'ERROR',
                'message' => 'Unauthorized',
            ], 401);
        }

        // Composer

        $fault = false;

        try {
            $dependencies = $this->getComposerDependencies();
        } catch (Exception $e) {
            $fault = true;
            $composer = [
                'status' => 'ERROR',
                'message' => $e->getMessage(),
            ];
        }

        if ($fault == false) {
            $composer = [
                'status' => 'OK',
                'dependencies' => $dependencies,
            ];
        }

        // Npm

        $fault = false;

        try {
            $dependencies = $this->getNpmDependencies();
        } catch (Exception $e) {
            $fault = true;
            $npm = [
                'status' => 'ERROR',
                'message' => $e->getMessage(),
            ];
        }

        if ($fault == false) {
            $npm = [
                'status' => 'OK',
                'dependencies' => $dependencies,
            ];
        }

        return new JsonResponse([
            'status' => 'OK',
            'versions' => [
                'webserver' => [
                    'name' => $this->getWebserverName(),
                    'version' => $this->getWebserverVersion(),
                ],
                'database' => [
                    'name' => $this->getDatabaseDriverName(),
                    'version' => $this->getDatabaseVersion(),
                ],
                'php' => [
                    'name' => php_sapi_name(),
                    'version' => phpversion(),
                ],
                'composer' => [
                    'response' => $composer,
                ],
                'npm' => [
                    'response' => $npm,
                ],
            ],
        ], 200);
    }

    /**
     * Get Webserver Name.
     */
    public function getWebserverName()
    {
        $serverSoftware = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'N/A';

        if (strpos($serverSoftware, 'Apache') !== false) {
            return 'Apache';
        } elseif (strpos($serverSoftware, 'nginx') !== false) {
            return 'Nginx';
        } else {
            return 'N/A';
        }
    }

    /**
     * Get Webserver Version.
     */
    public function getWebserverVersion()
    {
        $serverSoftware = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'N/A';

        if (strpos($serverSoftware, 'Apache') !== false) {
            return function_exists('apache_get_version') ? apache_get_version() : $serverSoftware;
        } elseif (strpos($serverSoftware, 'nginx') !== false) {
            return $serverSoftware;
        } else {
            return $serverSoftware;
        }
    }

    /**
     * Get Database Driver Name.
     */
    public function getDatabaseDriverName()
    {
        if (empty($_ENV['TUIJNCODE_VERSION_PDO_DSN'])) {
            return 'N/A';
        }

        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Get Database Version.
     */
    public function getDatabaseVersion()
    {
        $driver = $this->getDatabaseDriverName();

        switch ($driver) {
            case 'mysql':
            case 'mariadb':
                return $this->pdo->query('SELECT VERSION()')->fetchColumn();
            case 'pgsql':
                return $this->pdo->query('SHOW server_version')->fetchColumn();
            case 'sqlite':
                return sqlite_libversion();
            case 'sqlsrv':
                return $this->pdo->query("SELECT SERVERPROPERTY('ProductVersion') as version")->fetchColumn();
            default:
                return 'N/A';
        }
    }

    /**
     * Get Composer Dependencies.
     */
    public function getComposerDependencies()
    {
        $file = $_SERVER['DOCUMENT_ROOT'].'/composer.json';

        if (! file_exists($file)) {
            throw new Exception('File composer.json json found.');
        }

        $data = json_decode(file_get_contents($file), true);

        if (! isset($data['require'])) {
            throw new Exception('Invalid composer.json format.');
        }

        $dependencies = [];

        foreach (['require-dev', 'require'] as $type) {
            if (! array_key_exists($type, $data)) {
                continue;
            }

            foreach ($data[$type] as $name => $version) {
                $dependencies[$name] = [
                    'version' => $version,
                    'type' => $type,
                ];
            }
        }

        return $dependencies;
    }

    /**
     * Get Npm Dependencies.
     */
    public function getNpmDependencies()
    {
        $path = $_SERVER['DOCUMENT_ROOT'].'/package.json';

        if (! file_exists($path)) {
            throw new Exception('File package.json not found.');
        }

        $data = json_decode(file_get_contents($path), true);

        if (! isset($data['dependencies'])) {
            throw new Exception('Invalid package.json format.');
        }

        foreach (['devDependencies', 'dependencies'] as $type) {
            if (! array_key_exists($type, $data)) {
                continue;
            }

            foreach ($data[$type] as $name => $version) {
                $dependencies[$name] = [
                    'version' => $version,
                    'type' => $type,
                ];
            }
        }

        return $dependencies;
    }
}
