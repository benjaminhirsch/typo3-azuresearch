<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$pluginName = 'SearchForm';

// Add allowed records to pages
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_azuresearch_index');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    $_EXTKEY,                       // Extension Key
    'Configuration/TypoScript',     // Path to setup.txt and constants.txt
    'Azure Search'                  // Title in the selector box
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    $_EXTKEY,
    $pluginName,
    'Microsoft Azure Search'
);

$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);

$iconRegistry->registerIcon(
    'microsoft-azure-search',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    ['source' => 'EXT:azuresearch/ext_icon.png']
);

$extensionName = strtolower(\TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($_EXTKEY));
$pluginSignature = $extensionName.'_' . strtolower($pluginName);

$TCA['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';