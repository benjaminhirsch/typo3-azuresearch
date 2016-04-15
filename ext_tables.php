<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

// Add allowed records to pages
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_azuresearch_index');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    $_EXTKEY,                       // Extension Key
    'Configuration/TypoScript',     // Path to setup.txt and constants.txt
    'Azure Search'                  // Title in the selector box
);

$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);

$iconRegistry->registerIcon(
    'microsoft-azure-search',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    ['source' => 'EXT:azuresearch/ext_icon.png']
);