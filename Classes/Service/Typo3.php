<?php

namespace B3N\Azure\Typo3\Service;

use TYPO3\CMS\Core\Database\DatabaseConnection;

class Typo3
{

    /**
     * Get uid of the root page for the given pid
     *
     * @param DatabaseConnection $connection
     * @param $pid
     *
     * @return array
     */
    public static function getPageRoot(DatabaseConnection $connection, $pid)
    {
        $whereStatement = 'pages.deleted = 0 and pages.hidden = 0 AND pages.uid = ' . $pid;
        $databaseResource = $connection->exec_SELECTquery(
            '*',
            'pages',
            $whereStatement,
            ''
        );

        while (true) {
            $page = $connection->sql_fetch_assoc($databaseResource);

            if (!$page) {
                break;
            }

            if ($page['pid'] == 0) {
                return $page;
            }

            return self::getPageRoot($connection, $page['pid']);
        }
    }

    /**
     * @param DatabaseConnection $connection
     * @param $pid
     * 
     * @return bool|mixed
     */
    public static function getIndexTitleForPid(DatabaseConnection $connection, $pid)
    {
        
        $page = self::getPageRoot($connection, $pid);

        if (isset($page['uid'])) {

            $index = $connection->exec_SELECTgetSingleRow('title', 'tx_azuresearch_index', 'pid = ' . $page['uid']);

            if (is_array($index) && isset($index['title'])) {
                return $index['title'];
            }
        }
        
        return false;
    }

    /**
     * @param DatabaseConnection $connection
     * @param $uid
     * @return bool|string
     */
    public static function loadIndexTitleByUid(DatabaseConnection $connection, $uid)
    {
        $index = $connection->exec_SELECTgetSingleRow('title', 'tx_azuresearch_index', 'uid = ' . $uid);

        if (is_array($index) && isset($index['title'])) {
            return $index['title'];
        }
        
        return false;
    }
}