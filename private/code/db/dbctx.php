<?php

namespace Code\Db;

use PDO;


/**
 * Creating clause items
 */
function makeClause(array $input)
{
    $ret = [];
    foreach ($input as $v) {
        $ret[] = '`' . $v . '`=:' . $v;
    }
    return $ret;
}

/**
 * adding backquotes so the items can be used in an SQL
 */
function addBackQuotes(array $input)
{
    $ret = [];
    foreach ($input as $v) {
        $ret[] = '`' . $v . '`';
    }
    return $ret;
}

/**
 * as placeholder names
 */
function prependColon(array $input)
{
    $ret = [];
    foreach ($input as $v) {
        $ret[] = ':' . $v;
    }
    return $ret;
}

class DbCtx
{
    private static $instance;
    private PDO $pdo;
    private $prefix;

    /**
     * only getCtx gets to construct a new instance
     */
    private function __construct()
    {
        global $config;
        $dbCfg = $config->database;
        $tz = $config->timezone ?? 'utc';
        $this->pdo = new \PDO('mysql:host=' . $dbCfg->server . ';dbname=' . $dbCfg->database . ';timezone=' . $tz,
            $dbCfg->user, $dbCfg->password);
        $this->pdo->exec('SET time_zone = \'' . $tz . '\' ');
        self::$instance = $this;
        $this->prefix = $dbCfg->prefix . '_' ?? '';
        // error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . ' db-pdo constructed');
    }

    public static function getCtx(): DbCtx
    {
        return self::$instance ?? new self();
    }


    /**
     * upgrading the database using an idempotent SQL script
     * @return void
     */
    public function upgradeDatabase(string $dbf): void
    {
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__);
        $content = file_get_contents($dbf);
        $content = str_replace('${prefix}', $this->prefix, $content);
        // splitting the file at each occurance '^-- 2020-01-01 or similar dates'
        $pattern = '/^(-- \d{4}-\d{2}-\d{2}.*)$/m';
        $result = preg_split($pattern, $content, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        // running each part as a batch, it should not fail
        $lastSuccess = 'start of file';
        foreach ($result as $sqlParts) {
            $sqlParts = trim($sqlParts);
            if (preg_match($pattern, $sqlParts) == 1) {
                // this is a line '-- 2020-01-01...'
                error_log("db update up to $sqlParts");
                $lastSuccess = $sqlParts;
            } else {
                if ($sqlParts == '') {
                    // empty queries will fail, hence skipping them
                    continue;
                }
                try {
                    $stmt=$this->pdo->exec($sqlParts);
                } catch (\PDOException $e) {
                    $msg = $e->getMessage();
                    error_log("got an exception $msg");
                    error_log($sqlParts);
                    break;
                }
            }
        }
    }


}
