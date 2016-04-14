<?php

namespace B3N\Azure\Typo3\Hook;

use B3N\Azure\Index;
use B3N\Azure\Search;
use B3N\Azure\Typo3\Factory\AzureSearch;
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

    public function __construct()
    {
        $this->azure = AzureSearch::getInstance();
        $this->db = $GLOBALS['TYPO3_DB'];
    }

    public function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, &$pObj)
    {
        /*
        file_put_contents('command.txt', print_r($status, true));
        file_put_contents('table.txt', print_r($table, true));
        file_put_contents('recordId.txt', $id);
        file_put_contents('commandValue.txt', print_r($fieldArray, true));
        file_put_contents('tceMain.txt', print_r($pObj, true));
        */

        //@TODO prevent name edit in the TYPO3 backend

        // New index? Create fields
        if ($table == 'tx_azuresearch_index') {
            if ($status == 'new') {
                $index = new Index($fieldArray['title']);
                $index->addField(new Index\Field('uid', Index\Field::TYPE_STRING, true, false))
                    ->addField(new Index\Field('title', Index\Field::TYPE_STRING))
                    ->addField(new Index\Field('pid', Index\Field::TYPE_INT32, false, false, false, false, false))
                    ->addField(new Index\Field('sys_language_uid', Index\Field::TYPE_INT32, false, false, false, false, false))
                    ->addField(new Index\Field('content', Index\Field::TYPE_STRING))
                    ->addSuggesters(new Index\Suggest('title', ['title']));
                $this->azure->createIndex($index);
            }
        }
    }

    public function processCmdmap_deleteAction($table, $id, $recordToDelete, $recordWasDeleted, $pObj)
    {
        
        /*file_put_contents('table.txt', print_r($table, true));
        file_put_contents('id.txt', $id);
        file_put_contents('recordToDelete.txt', print_r($recordToDelete, true));
        file_put_contents('recordWasDeleted.txt', print_r($recordWasDeleted, true));
        file_put_contents('pObj.txt', print_r($this, true));*/

        if ($table == 'tt_content') {
            $index = Typo3::getIndexTitleForPid($this->db, $recordToDelete['pid']);
            if ($index) {
                file_put_contents('deleteIndex.txt', print_r([$index, $id], true));

                $record['value'][] = [
                    '@search.action' => Index::ACTION_DELETE,
                    'uid' => (string)$id,
                ];
                
                $response = $this->azure->uploadToIndex($index, $record);

                //file_put_contents('response.txt', print_r($response, true));
                //file_put_contents('body.txt', json_encode($record));
            }
        }
    }
}