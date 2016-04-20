<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/Configuration/PageTS/TCEFORM.txt">'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
    'B3N\Azure\Typo3\Azuresearch\Controller\AzureIndexCommandController';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
    'EXT:' . $_EXTKEY . '/Classes/Azuresearch/Hook/Actions.php:B3N\Azure\Typo3\Azuresearch\Hook\Actions';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] =
    'EXT:' . $_EXTKEY . '/Classes/Azuresearch/Hook/Actions.php:B3N\Azure\Typo3\Azuresearch\Hook\Actions';

// Register custom form evaluation
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][\B3N\Azure\Typo3\Azuresearch\Evaluator\Indexname::class] = '';

// register extbase plugin
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'B3N.Azure.Typo3.' . $_EXTKEY,
    'SearchForm',
    ['AzureSearch' => 'index'],
    ['AzureSearch' => 'index']
);