<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     *
     * @var array<string, mixed>
     */
    public array $default = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => '',
        'password'     => '',
        'database'     => '',
        'schema'       => 'public',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8mb4',
        'DBCollat'     => 'utf8mb4_general_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
        'numberNative' => false,
        'foundRows'    => false,
        'dateFormat'   => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    //    /**
    //     * Sample database connection for SQLite3.
    //     *
    //     * @var array<string, mixed>
    //     */
    //    public array $default = [
    //        'database'    => 'database.db',
    //        'DBDriver'    => 'SQLite3',
    //        'DBPrefix'    => '',
    //        'DBDebug'     => true,
    //        'swapPre'     => '',
    //        'failover'    => [],
    //        'foreignKeys' => true,
    //        'busyTimeout' => 1000,
    //        'synchronous' => null,
    //        'dateFormat'  => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    // /**
    //  * Sample database connection for Postgre.
    //  *
    //  * @var array<string, mixed>
    //  */
    // public array $default = [
    //     'DSN' => '',
    //     'hostname' => 'localhost',
    //     'username' => 'root',
    //     'password' => 'root',
    //     'database' => 'ci4',
    //     'schema' => 'public',
    //     'DBDriver' => 'Postgre',
    //     'DBPrefix' => '',
    //     'pConnect' => false,
    //     'DBDebug' => true,
    //     'charset' => 'utf8',
    //     'swapPre' => '',
    //     'failover' => [],
    //     'port' => 5432,
    //     'dateFormat' => [
    //         'date' => 'Y-m-d',
    //         'datetime' => 'Y-m-d H:i:s',
    //         'time' => 'H:i:s',
    //     ],
    // ];

    // /**
    //  * Sample database connection for SQLSRV.
    //  *
    //  * @var array<string, mixed>
    //  */
    //    public array $default = [
    //        'DSN'        => '',
    //        'hostname'   => 'localhost',
    //        'username'   => 'root',
    //        'password'   => 'root',
    //        'database'   => 'ci4',
    //        'schema'     => 'dbo',
    //        'DBDriver'   => 'SQLSRV',
    //        'DBPrefix'   => '',
    //        'pConnect'   => false,
    //        'DBDebug'    => true,
    //        'charset'    => 'utf8',
    //        'swapPre'    => '',
    //        'encrypt'    => false,
    //        'failover'   => [],
    //        'port'       => 1433,
    //        'dateFormat' => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    // /**
    //  * Sample database connection for OCI8.
    //  *
    //  * You may need the following environment variables:
    //  *   NLS_LANG                = 'AMERICAN_AMERICA.UTF8'
    //  *   NLS_DATE_FORMAT         = 'YYYY-MM-DD HH24:MI:SS'
    //  *   NLS_TIMESTAMP_FORMAT    = 'YYYY-MM-DD HH24:MI:SS'
    //  *   NLS_TIMESTAMP_TZ_FORMAT = 'YYYY-MM-DD HH24:MI:SS'
    //  *
    //  * @var array<string, mixed>
    //  */
    //    public array $default = [
    //        'DSN'        => 'localhost:1521/XEPDB1',
    //        'username'   => 'root',
    //        'password'   => 'root',
    //        'DBDriver'   => 'OCI8',
    //        'DBPrefix'   => '',
    //        'pConnect'   => false,
    //        'DBDebug'    => true,
    //        'charset'    => 'AL32UTF8',
    //        'swapPre'    => '',
    //        'failover'   => [],
    //        'dateFormat' => [
    //            'date'     => 'Y-m-d',
    //            'datetime' => 'Y-m-d H:i:s',
    //            'time'     => 'H:i:s',
    //        ],
    //    ];

    /**
     * This database connection is used when running PHPUnit database tests.
     *
     * @var array<string, mixed>
     */
    public array $tests = [
        'DSN' => '',
        'hostname' => '127.0.0.1',
        'username' => '',
        'password' => '',
        'database' => ':memory:',
        'DBDriver' => 'SQLite3',
        'DBPrefix' => 'db_',  // Needed to ensure we're working correctly with prefixes live. DO NOT REMOVE FOR CI DEVS
        'pConnect' => false,
        'DBDebug' => true,
        'charset' => 'utf8',
        'DBCollat' => '',
        'swapPre' => '',
        'encrypt' => false,
        'compress' => false,
        'strictOn' => false,
        'failover' => [],
        'port' => 3306,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
        'dateFormat' => [
            'date' => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time' => 'H:i:s',
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        // Read environment variables at runtime (cannot be used in property defaults).
        $this->default['hostname'] = (string) (getenv('DB_HOST') ?: (env('database.default.hostname') ?: $this->default['hostname']));
        $this->default['username'] = (string) (getenv('DB_USER') ?: (env('database.default.username') ?: $this->default['username']));
        $this->default['password'] = (string) (getenv('DB_PASS') ?: (env('database.default.password') ?: $this->default['password']));
        $this->default['database'] = (string) (getenv('DB_NAME') ?: (env('database.default.database') ?: $this->default['database']));
        $this->default['schema']   = (string) (getenv('DB_SCHEMA') ?: (env('database.default.schema') ?: $this->default['schema']));
        $this->default['DBDriver'] = (string) (getenv('DB_DRIVER') ?: (env('database.default.DBDriver') ?: $this->default['DBDriver']));
        $this->default['port']     = (int) (getenv('DB_PORT') ?: (env('database.default.port') ?: $this->default['port']));

        $driver = strtolower((string) ($this->default['DBDriver'] ?? ''));

        if (str_contains($driver, 'postgre') || str_contains($driver, 'pgsql')) {
            if (($this->default['charset'] ?? null) === 'utf8mb4') {
                $this->default['charset'] = 'utf8';
            }

            if (($this->default['DBCollat'] ?? null) === 'utf8mb4_general_ci') {
                $this->default['DBCollat'] = '';
            }
        }

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite, so that
        // we don't overwrite live data on accident.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }
    }
}
