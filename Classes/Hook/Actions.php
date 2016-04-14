<?php

namespace B3N\Azure\Typo3\Hook;

use B3N\Azure\Index;
use B3N\Azure\Search;
use B3N\Azure\Typo3\Factory\AzureSearch;

class Actions
{

    /** @var  Search */
    private $azure;

    public function __construct()
    {
        $this->azure = AzureSearch::getInstance();
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
                $index->addField(new Index\Field('key', Index\Field::TYPE_STRING, true, false))
                    ->addField(new Index\Field('title', Index\Field::TYPE_STRING))
                    ->addField(new Index\Field('uid', Index\Field::TYPE_INT32, false, false, false, false, false))
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
        /*
        file_put_contents('table.txt', print_r($table, true));
        file_put_contents('id.txt', $id);
        file_put_contents('recordToDelete.txt', print_r($recordToDelete, true));
        file_put_contents('recordWasDeleted.txt', print_r($recordWasDeleted, true));
        file_put_contents('pObj.txt', print_r($this, true));
        */

        //$this->azure->deleteIndex($recordToDelete['title']);
    }
}