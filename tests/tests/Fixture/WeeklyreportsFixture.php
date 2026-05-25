<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * WeeklyreportsFixture
 *
 */
class WeeklyreportsFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 10, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'project_id' => ['type' => 'integer', 'length' => 10, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'title' => ['type' => 'string', 'length' => 50, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'week' => ['type' => 'integer', 'length' => 2, 'null' => false, 'default' => null, 'comment' => ''],
        'year' => ['type' => 'integer', 'length' => 4, 'null' => false, 'default' => null, 'comment' => ''],
        /*'reglink' => ['type' => 'string', 'length' => 100, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null], */
        'problems' => ['type' => 'string', 'length' => 400, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'meetings' => ['type' => 'string', 'length' => 400, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'additional' => ['type' => 'string', 'length' => 400, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'created_on' => ['type' => 'date', 'null' => false, 'default' => null],
        'updated_on' => ['type' => 'date', 'null' => true, 'default' => null],
        'created_by' => ['type' => 'integer', 'length' => 11, 'null' => true, 'default' => null],
        'updated_by' => ['type' => 'integer', 'length' => 11, 'null' => true, 'default' => null],
        '_indexes' => [
            'project_key' => ['type' => 'index', 'columns' => ['project_id'], 'length' => []],
        ],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
            'weeklyreports_ibfk_1' => ['type' => 'foreign', 'columns' => ['project_id'], 'references' => ['projects', 'id'], 'update' => 'restrict', 'delete' => 'restrict', 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
            'collation' => 'utf8_general_ci'
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => 1,
            'project_id' => 1,
            'title' => 'Lorem ipsum dolor sit amet',
            'week' => 1,  
            'year' => 2015, 
            /*'reglink' => 'http://example.com',*/
            'problems' => 'Some reported issues',
            'meetings' => 'Meeting details here',
            'additional' => 'Additional notes',
            'created_on' => '2015-01-01',
            'updated_on' => '2015-01-01',
            'created_by' => 1,
            'updated_by' => 1
        ],
    ];
}
