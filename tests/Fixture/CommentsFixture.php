<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * CommentsFixture
 */
class CommentsFixture extends TestFixture
{  
    /**
     * Fields
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true, 'precision' => null],
        'user_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'precision' => null],
        'weeklyreport_id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'precision' => null],
        'content' => ['type' => 'string', 'length' => 1000, 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'precision' => null, 'fixed' => null],
        'date_created' => ['type' => 'timestamp', 'length' => null, 'null' => false, 'default' => 'CURRENT_TIMESTAMP', 'precision' => null],
        'date_modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'precision' => null],
        '_indexes' => [
            'user_id' => ['type' => 'index', 'columns' => ['user_id'], 'length' => []],
            'weeklyreport_id' => ['type' => 'index', 'columns' => ['weeklyreport_id'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8_general_ci'
        ],
    ];

    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => 1,
            'user_id' => 1,
            'weeklyreport_id' => 1,
            'content' => 'Test comment',
            'date_created' => '2015-0-01',
            'date_modified' => '2015-1-01',
        ],
        [
            'id' => 2,
            'user_id' => 2,
            'weeklyreport_id' => 2,
            'content' => 'Test comment',
            'date_created' => '2015-0-02',
            'date_modified' => '2015-1-02',
        ],
    ];
}