<?php

namespace B3N\Azure\Typo3\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Lang\Domain\Repository\LanguageRepository;
use TYPO3\CMS\Version\Hook\DataHandlerHook;
use B3N\Azure\Typo3\Factory\AzureSearch;

class AzureIndexCommandController extends CommandController
{

    /**
     * @var PageRepository
     */
    private $pageRepository;

    public function indexerCommand()
    {

        // Load Azure Service
        //AzureSearch::getInstance()

        // Load page repository
        $this->pageRepository = $this->objectManager->get(PageRepository::class);


        ///** @var LanguageRepository $lang */
        //$lang = $this->objectManager->get(LanguageRepository::class);
        //var_dump($lang->());

        // Load database connection
        $db = $this->getDatabaseConnection();

        $this->output->outputLine("Updating Azure Search index" . PHP_EOL);

        $whereStatement = 'pages.deleted = 0';
        $databaseResource = $db->exec_SELECTquery(
            '*',
            'pages',
            $whereStatement,
            '',
            'sorting'
        );

        // Count how many pages exists for indexing
        $numPages = $db->exec_SELECTcountRows('pid', 'pages', $whereStatement);

        $this->output->progressStart($numPages);

        while (($page = $db->sql_fetch_assoc($databaseResource))) {

            // Versioning Preview Overlay
            $this->pageRepository->versionOL('pages', $page, true);
            // Skip if page got disabled due to version overlay
            // (might be delete or move placeholder)
            if (empty($page)) {
                continue;
            }

            // Add a mount point parameter if needed
            //$page = $this->pageRepository->addMountPointParameterToPage((array)$page);
            // $page MUST have "uid", "pid", "doktype", "mount_pid", "mount_pid_ol" fields in it
            $mountPointInfo = $this->pageRepository->getMountPointInfo($page['uid'], $page);

            // There is a valid mount point.
            if (is_array($mountPointInfo) && $mountPointInfo['overlay']) {

                // Using "getPage" is OK since we need the check for enableFields AND for type 2
                // of mount pids we DO require a doktype < 200!
                $mountPointPage = $this->pageRepository->getPage($mountPointInfo['mount_pid']);

                if (!empty($mountPointPage)) {
                    $page = $mountPointPage;
                    $page['_MP_PARAM'] = $mountPointInfo['MPvar'];
                } else {
                    $page = [];
                }
            }

            // If shortcut, look up if the target exists and is currently visible
            $dokType = (int)$page['doktype'];
            $shortcutMode = (int)$page['shortcut_mode'];

            if ($dokType === PageRepository::DOKTYPE_SHORTCUT && ($page['shortcut'] || $shortcutMode)) {
                if ($shortcutMode === PageRepository::SHORTCUT_MODE_NONE) {
                    // No shortcut_mode set, so target is directly set in $page['shortcut']
                    $searchField = 'uid';
                    $searchUid = (int)$page['shortcut'];
                } elseif ($shortcutMode === PageRepository::SHORTCUT_MODE_FIRST_SUBPAGE || $shortcutMode === PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE) {
                    // Check subpages - first subpage or random subpage
                    $searchField = 'pid';
                    // If a shortcut mode is set and no valid page is given to select subpags
                    // from use the actual page.
                    $searchUid = (int)$page['shortcut'] ?: $page['uid'];
                } elseif ($shortcutMode === PageRepository::SHORTCUT_MODE_PARENT_PAGE) {
                    // Shortcut to parent page
                    $searchField = 'uid';
                    $searchUid = $page['pid'];
                } else {
                    $searchField = '';
                    $searchUid = 0;
                }

                $whereStatement.= ' AND ' . $searchField . '=' . $searchUid;

                $count = $this->getDatabaseConnection()->exec_SELECTcountRows(
                    'uid',
                    'pages',
                    $whereStatement
                );

                if (!$count) {
                    $page = [];
                }
            } elseif ($dokType === PageRepository::DOKTYPE_SHORTCUT) {
                // Neither shortcut target nor mode is set. Remove the page from the menu.
                $page = [];
            }

            // If the page still is there, we add it to the output
            if (!empty($page)) {
                //$pages[$originalUid] = $page;

                // Load conent for the current page
                $res = $this->getDatabaseConnection()->exec_SELECTquery('*', 'tt_content', 'pid =' . $this->getDatabaseConnection()->fullQuoteStr($page['uid'], 'tt_content') . $this->pageRepository->deleteClause('tt_content'));
                $rows = array();
                while ($row = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                    if (is_array($row)) {
                        $rows[] = $row;
                    }
                }
                $this->getDatabaseConnection()->sql_free_result($res);
                if (!empty($rows)) {
                        // Content
                        //var_dump($rows);
                }
            }

            $this->output->progressAdvance();
        }

       $this->output->progressFinish();
       $this->output->outputLine(PHP_EOL);



        $db->sql_free_result($databaseResource);
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