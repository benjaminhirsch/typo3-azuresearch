<?php

namespace B3N\Azure\Typo3\Azuresearch\Controller;

use B3N\Azure\Typo3\Azuresearch\Exception\IndexNotFoundException;
use B3N\Azure\Typo3\Azuresearch\Exception\MissingIndexException;
use B3N\Azure\Typo3\Azuresearch\Factory\AzureSearch;
use B3N\Azure\Typo3\Azuresearch\Service\Typo3;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class AzureSearchController extends ActionController
{

    /**
     * @throws IndexNotFoundException
     * @throws MissingIndexException
     * @throws \B3N\Azure\Typo3\Azuresearch\Exception\MissingExtensionSettingException
     */
    public function indexAction()
    {

        if (!isset($this->settings['azuresearch']) || $this->settings['azuresearch'] == '') {
            throw new MissingIndexException('No Microsoft Azure Index selected.');
        }

        // Load index
        $index = Typo3::loadIndexTitleByUid($GLOBALS['TYPO3_DB'], $this->settings['azuresearch']);
        
        if ($index === false) {
            throw new IndexNotFoundException('The selected index could not be found! Please check your plugin settings.');
        }

        $args = $this->request->getArguments();

        if (isset($args['q'])) {
            $azure = new AzureSearch();
            $res = $azure->getInstance()->search($index, $args['q'], [
                'filter' => 'sys_language_uid eq ' . $GLOBALS['TSFE']->sys_language_uid
            ]);

            $this->view->assign('res', $res);
            $this->view->assign('q', htmlspecialchars(strip_tags($args['q'])));
        }

    }

    public function livesearchAction()
    {

    }
}