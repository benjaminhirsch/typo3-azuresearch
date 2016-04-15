<?php

namespace B3N\Azure\Typo3\Controller;

use B3N\Azure\Index;
use B3N\Azure\Typo3\Factory\AzureSearch;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Frontend\Page\PageRepository;

class AzureIndexCommandController extends CommandController
{

    /**
     * @var PageRepository
     */
    private $pageRepository;

    /**
     * @var DatabaseConnection
     */
    private $db;

    /**
     * @var AzureSearch
     */
    private $azure;

    /**
     * @var array
     */
    private $batch = [];

    /**
     * @var string
     */
    private $currentIndex;

    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    private $logger;

    /**
     * @var int
     */
    private $documents = 0;

    /**
     * @var int
     */
    private $indexes = 0;

    public function __construct()
    {
        $this->db = $GLOBALS['TYPO3_DB'];
        $this->azure = AzureSearch::getInstance();
        $this->logger = \B3N\Azure\Typo3\Factory\Logger::getInstance();
    }

    public function indexerCommand()
    {
        // Load page repository
        $this->pageRepository = $this->objectManager->get(PageRepository::class);

        // Load database connection
        $db = $this->db;

        $databaseResource = $db->exec_SELECTquery(
            '*',
            'tx_azuresearch_index',
            ''
        );

        $indexes = $databaseResource->fetch_all(MYSQLI_ASSOC);
        foreach ($indexes as $index) {
            $this->indexes++;
            $this->currentIndex = $index['title'];
            $this->getPage($index['pid']);
        }

        // Submit last entries
        $this->submitBatch();

        $this->logger->info('Azure indexer: Indexes ['.$this->indexes.'], Documents [' . $this->documents . ']' . PHP_EOL);
    }

    private function getPage($pid)
    {
        $whereStatement = 'pages.deleted = 0 and pages.hidden = 0 AND pages.pid = ' . $pid;
        $databaseResource = $this->db->exec_SELECTquery(
            '*',
            'pages',
            $whereStatement,
            ''
        );

        while (true) {
            $page = $this->db->sql_fetch_assoc($databaseResource);

            if (!$page) {
                break;
            }

            $this->getPage($page['uid']);

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

                $whereStatement .= ' AND ' . $searchField . '=' . $searchUid;

                $count = $this->db->exec_SELECTcountRows(
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
                // Load conent for the current page
                $res = $this->db->exec_SELECTquery('*', 'tt_content', 'pid =' . $this->db->fullQuoteStr($page['uid'],
                        'tt_content') . $this->pageRepository->deleteClause('tt_content'));
                $rows = [];
                while ($row = $this->db->sql_fetch_assoc($res)) {
                    if (is_array($row)) {
                        $rows[] = $row;
                    }
                }
                $this->db->sql_free_result($res);
                if (!empty($rows)) {

                    // Content
                    foreach ($rows as $row) {

                        $this->documents++;

                        if (count($this->batch) == 1000) {
                            $this->submitBatch();
                        }

                        $this->batch['value'][] = [
                            '@search.action' => Index::ACTION_MERGE_OR_UPLOAD,
                            'uid' => (string)$row['uid'],
                            'title' => $page['title'],
                            'header' => $row['header'],
                            'ctype' => $row['CType'],
                            'categories' => $row['categories'],
                            'tstamp' => $row['tstamp'],
                            't3ver_stage' => $row['t3ver_stage'],
                            'pid' => $row['pid'],
                            'sys_language_uid' => $row['sys_language_uid'],
                            'l18n_parent' => $row['l18n_parent'],
                            'bodytext' => $row['bodytext']
                        ];
                    }
                }
            }
        }
    }

    private function submitBatch()
    {
        $this->azure->uploadToIndex($this->currentIndex, $this->batch);
    }
}