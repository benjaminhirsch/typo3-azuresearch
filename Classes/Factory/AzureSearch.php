<?php

namespace B3N\Azure\Typo3\Factory;

use B3N\Azure\Search;
use B3N\Azure\Typo3\Exception\MissingExtensionSettingException;

class AzureSearch
{

    /**
     * @throws MissingExtensionSettingException
     */
    public static function getInstance() : Search
    {

        // Load extension settings
        $config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['azuresearch']);



        if (isset($config['azureUrl']) && $config['azureUrl'] != '' &&
            isset($config['azureAdminKey']) && $config['azureAdminKey'] != '' &&
            isset($config['azureVersion']) && $config['azureVersion'] != '') {
            
            // Return pre configured  Azure Search instance
            return new Search($config['azureUrl'], $config['azureAdminKey'], $config['azureVersion']);
        }
        
        throw new MissingExtensionSettingException ('Missing Arguments for Microsoft Azure Service. Please have a look at the extension settings in the TYPO3 backend.');
    }
}