<?php
/**
 * User: yongli
 * Date: 17/4/23
 * Time: 18:59
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace YP\Config;

class Language
{
    /**
     * 存放缓存短语
     *
     * @var array
     */
    public $cache = [];

    /**
     * 存放命令行短语
     *
     * @var array
     */
    public $cli = [];

    /**
     * 存放语言短语
     *
     * @var array
     */
    public $language = [];

    /**
     * 存放迁移短语
     *
     * @var array
     */
    public $migrations = [];

    /**
     * Language constructor.
     */
    public function __construct() { }

    /**
     * 初始化缓存短语
     *
     * @return array
     */
    public function initCache()
    {
        $this->cache = [
            'cacheInvalidHandlers' => 'Cache config must have an array of $validHandlers.',
            'cacheNoBackup'        => 'Cache config must have a handler and backupHandler set.',
            'cacheHandlerNotFound' => 'Cache config has an invalid handler or backup handler specified.',
        ];

        return $this->cache;
    }

    /**
     * 初始化命令行短语
     *
     * @return array
     */
    public function initCli()
    {
        $this->cli = [
            'helpUsage'       => 'Usage:',
            'helpDescription' => 'Description:',
            'helpOptions'     => 'Options:',
            'helpArguments'   => 'Arguments:',
        ];

        return $this->cli;
    }

    /**
     * 初始化语言短语
     *
     * @return array
     */
    public function initLanguage()
    {
        $this->language = [
            'languageGetLineInvalidArgumentException' => 'Get line must be a string or array of strings.'
        ];

        return $this->language;
    }

    /**
     * 初始化迁移短语
     *
     * @return array
     */
    public function initMigrations()
    {
        $this->migrations = [
            'migMissingTable'   => 'Migrations table must be set.',
            'migInvalidType'    => 'An invalid migration numbering type was specified: ',
            'migDisabled'       => 'Migrations have been loaded but are disabled or setup incorrectly.',
            'migNotFound'       => 'Migration file not found: ',
            'migEmpty'          => 'No Migration files found',
            'migGap'            => 'There is a gap in the migration sequence near version number: ',
            'migClassNotFound'  => 'The migration class "%s" could not be found.',
            'migMissingMethod'  => 'The migration class is missing an "%s" method.',
            'migMultiple'       => 'There are multiple migrations with the same version number: ',
            // Migration Command
            'migHelpLatest'     => "\t\tMigrates database to latest available migration.",
            'migHelpCurrent'    => "\t\tMigrates database to version set as 'current' in configuration.",
            'migHelpVersion'    => "\tMigrates database to version {v}.",
            'migHelpRollback'   => "\tRuns all migrations 'down' to version 0.",
            'migHelpRefresh'    => "\t\tUninstalls and re-runs all migrations to freshen database.",
            'migHelpSeed'       => "\tRuns the seeder named [name].",
            'migCreate'         => "\tCreates a new migration named [name]",
            'migNameMigration'  => "Name the migration file",
            'migBadCreateName'  => 'You must provide a migration file name.',
            'migWriteError'     => 'Error trying to create file.',
            'migToLatest'       => 'Migrating to latest version...',
            'migInvalidVersion' => 'Invalid version number provided.',
            'migToVersionPH'    => 'Migrating to version %s...',
            'migToVersion'      => 'Migrating to current version...',
            'migRollingBack'    => "Rolling back all migrations...",
            'migNoneFound'      => 'No migrations were found.',
            'migOn'             => 'Migrated On: ',
            'migSeeder'         => 'Seeder name',
            'migMissingSeeder'  => 'You must provide a seeder name.',
            'migHistoryFor'     => 'Migration history For ',
            'migRemoved'        => 'Downgrade: ',
            'migAdded'          => 'Upgrade: ',
            'version'           => 'Version',
            'filename'          => 'Filename',
        ];

        return $this->migrations;
    }

}