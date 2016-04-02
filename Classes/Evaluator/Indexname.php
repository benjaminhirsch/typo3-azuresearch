<?php

namespace B3N\Azure\Typo3\Evaluator;

class Indexname
{
    function returnFieldJS() {
        return '
         return value;
      ';
    }
    function evaluateFieldValue($value, $is_in, &$set) {
        return $value;
    }

    /**
     * Returns the database connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    private function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}