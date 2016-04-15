<?php

namespace B3N\Azure\Typo3\Hook;

use B3N\Azure\Exception\LengthException;
use B3N\Azure\Exception\UnexpectedValueException;
use B3N\Azure\Index;
use B3N\Azure\Search;
use B3N\Azure\Typo3\Factory\AzureSearch;
use B3N\Azure\Typo3\Factory\Logger;
use B3N\Azure\Typo3\Service\Typo3;
use TYPO3\CMS\Core\Database\DatabaseConnection;

class Actions
{

    /** @var  Search */
    private $azure;

    /**
     * @var DatabaseConnection
     */
    private $db;

    /**
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    private $logger;

    public function __construct()
    {
        $this->azure = AzureSearch::getInstance();
        $this->db = $GLOBALS['TYPO3_DB'];
        $this->logger = Logger::getInstance();
    }

    public function processDatamap_afterDatabaseOperations($status, $table, $id, &$fieldArray, &$pObj)
    {
        $index = Typo3::getIndexTitleForPid($this->db, $fieldArray['pid']);

        if ($table == 'tt_content') {
            switch ($status) {

                // New content element
                case 'new':
                    $record['value'][] = [
                        '@search.action' => Index::ACTION_UPLOAD,
                        'uid' => (string)$pObj->substNEWwithIDs[$id],
                        'title' => $fieldArray['title'],
                        'header' => $fieldArray['header'],
                        'ctype' => $fieldArray['CType'],
                        'categories' => $fieldArray['categories'],
                        'tstamp' => $fieldArray['tstamp'],
                        't3ver_stage' => $fieldArray['t3ver_stage'],
                        'pid' => $fieldArray['pid'],
                        'sys_language_uid' => $fieldArray['sys_language_uid'],
                        'l18n_parent' => $fieldArray['l18n_parent'],
                        'bodytext' => $fieldArray['bodytext']
                    ];

                    try {
                        $response = $this->azure->uploadToIndex($index, $record);
                    } catch (UnexpectedValueException $e) {
                        $this->logger->error($e->getMessage());
                    } catch (LengthException $e) {
                        $this->logger->error($e->getMessage());
                    }

                    break;

                // Existing element
                case 'update':

                    try {
                        $index = $this->azure->getIndex($index);

                        // Remove unused fields
                        unset($fieldArray['l18n_diffsource']);

                        $record['value'][] = array_merge($fieldArray, [
                            '@search.action' => Index::ACTION_MERGE,
                            'uid' => (string)$id
                        ]);

                        $response = $this->azure->uploadToIndex($index->value[0]->name, $record);
                    } catch (UnexpectedValueException $e) {
                        $this->logger->error($e->getMessage());
                    } catch (LengthException $e) {
                        $this->logger->error($e->getMessage());
                    }

                    break;
            }

            if (isset($response)) {
                if ($response->isSuccess()) {
                    $this->logger->info($response->getContent() . PHP_EOL);
                } else {
                    $this->logger->error($response->getContent() . PHP_EOL);
                }
            }
        }
    }

    public function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, &$pObj)
    {
        //@TODO prevent name edit in the TYPO3 backend

        // New index? Create fields
        if ($table == 'tx_azuresearch_index') {
            if ($status == 'new') {
                $index = new Index($fieldArray['title']);
                $index->addField(new Index\Field('uid', Index\Field::TYPE_STRING, true, false))
                    ->addField(new Index\Field('title', Index\Field::TYPE_STRING))
                    ->addField(new Index\Field('header', Index\Field::TYPE_STRING))
                    ->addField(new Index\Field('ctype', Index\Field::TYPE_STRING, false, false, false, false, false))
                    ->addField(new Index\Field('categories', Index\Field::TYPE_INT32, false, false, false, false, false))
                    ->addField(new Index\Field('tstamp', Index\Field::TYPE_INT32, false, false, false, false, false))
                    ->addField(new Index\Field('t3ver_stage', Index\Field::TYPE_INT32, false, false, false, false, false))
                    ->addField(new Index\Field('pid', Index\Field::TYPE_INT32, false, false, false, false, false))
                    ->addField(new Index\Field('sys_language_uid', Index\Field::TYPE_INT32, false, false, false, false, false))
                    ->addField(new Index\Field('l18n_parent', Index\Field::TYPE_INT32, false, false, false, false, false))
                    ->addField(new Index\Field('bodytext', Index\Field::TYPE_STRING))
                    ->addSuggesters(new Index\Suggest('title', ['title', 'header']));
                $response = $this->azure->createIndex($index);

                if ($response->isSuccess()) {
                    $this->logger->info($response->getContent() . PHP_EOL);
                } else {
                    $this->logger->error($response->getContent() . PHP_EOL);
                }
            }
        }

    }

    public function processCmdmap_deleteAction($table, $id, $recordToDelete, $recordWasDeleted, $pObj)
    {
        if ($table == 'tt_content') {
            $index = Typo3::getIndexTitleForPid($this->db, $recordToDelete['pid']);
            if ($index) {
                $record['value'][] = [
                    '@search.action' => Index::ACTION_DELETE,
                    'uid' => (string)$id,
                ];

                try {
                    $response = $this->azure->uploadToIndex($index, $record);

                    if (!$response->isSuccess()) {
                        $this->logger->error($response->getContent() . PHP_EOL);
                    }
                } catch (LengthException $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }

        if ($table == 'tx_azuresearch_index') {

            $index = Typo3::loadIndexTitleByUid($this->db, $id);

            if ($index) {
                $response = $this->azure->deleteIndex($index);

                if (!$response->isSuccess()) {
                    $this->logger->error($response->getContent() . PHP_EOL);
                }
            }
        }
    }
}