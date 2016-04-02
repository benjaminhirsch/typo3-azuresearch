<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:'
    . $_EXTKEY . '/Configuration/PageTS/TCEFORM.txt">'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
    'B3N\Azure\Typo3\Controller\AzureIndexCommandController';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
    'EXT:' . $_EXTKEY. '/Classes/Hook/Actions.php:B3N\Azure\Typo3\Hook\Actions';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] =
    'EXT:' . $_EXTKEY. '/Classes/Hook/Actions.php:B3N\Azure\Typo3\Hook\Actions';

// Register custom form evaluation
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][\B3N\Azure\Typo3\Evaluator\Indexname::class] = '';