<?php
return [
    'ctrl' => [
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'adminOnly' => true,
        'title' => 'LLL:EXT:azuresearch/Resources/Private/Language/locallang.xlf:records.title',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'typeicon_classes' => [
            'default' => 'microsoft-azure-search'
        ],
        'searchFields' => 'title',
        //'readOnly' => true,
        'editlock' => 'editlock' // sql field needed https://docs.typo3.org/typo3cms/TCAReference/Reference/Ctrl/Index.html?highlight=editlock
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,title'
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:azuresearch/Resources/Private/Language/locallang.xlf:records.title',
            'config' => [
                'type' => 'input',
                'size' => '35',
                'max' => '80',
                'eval' => 'uniqueInPid,required,lower,trim,nospace,B3N\Azure\Typo3\Evaluator\Indexname',
                'softref' => 'substitute'
            ]
        ]
    ],
    'types' => [
        '1' => [
            'showitem' => 'hidden, title',
        ],
    ],
    'palettes' => [
        '1' => [],
    ]
];
