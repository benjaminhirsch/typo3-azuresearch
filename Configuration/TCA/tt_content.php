<?php
defined('TYPO3_MODE') or die();

call_user_func(function () {
    // Add the FlexForm
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        'azuresearch_searchform',
        'FILE:EXT:azuresearch/Configuration/FlexForms/azuresearch.xml'
    );
});