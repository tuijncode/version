<?php

namespace Tuijncode\Version;

use PDO;
use PDOException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Dotenv\Dotenv;

class RequestHandler
{
    protected $token;

    protected $pdo;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
        $dotenv->load();

        $this->token = isset($_ENV['TUIJNCODE_VERSION_TOKEN']) ? $_ENV['TUIJNCODE_VERSION_TOKEN'] : null;

        if (isset($_ENV['TUIJNCODE_VERSION_PDO_DSN'])) {
            try {
                $this->pdo = new PDO($_ENV['TUIJNCODE_VERSION_PDO_DSN'], $_ENV['TUIJNCODE_VERSION_PDO_USERNAME'], $_ENV['TUIJNCODE_VERSION_PDO_PASSWORD']);
            } catch (PDOException $e) {
                die('Connection failed: '.$e->getMessage());
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
                'message' => 'Token Is Not Configured Yet.'
            ], 400);
        }

        $request = Request::createFromGlobals();
        $requestToken = $request->query->get('token');

        if (empty($requestToken)) {
            return new JsonResponse([
                'status' => 'ERROR',
                'message' => 'Token Is Required.'
            ], 400);
        }

        if ($requestToken !== $this->token) {
            return new JsonResponse([
                'status' => 'ERROR',
                'message' => 'Unauthorized'
            ], 401);
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
                ]
            ]
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
}
