<?php

namespace B3N\Azure\Typo3\Azuresearch\Factory;

use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;

class Logger
{

    public static function getInstance() : \TYPO3\CMS\Core\Log\Logger
    {
        /** @var \TYPO3\CMS\Core\Log\Logger $logger */
        $logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);
        $logger->addWriter(LogLevel::INFO, new FileWriter([
            'logFile' => 'typo3temp/logs/azureindex.txt'
        ]));

        return $logger;
    }
}