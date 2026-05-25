<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * NotesFixture
 */
class NotesFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => null, 'autoIncrement' => true, 'precision' => null],
        'content' => ['type' => 'string', 'length' => 1000, 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'precision' => null, 'fixed' => null],
        'created_on' => ['type' => 'timestamp', 'length' => null, 'null' => false, 'default' => 'CURRENT_TIMESTAMP', 'precision' => null],
        'project_role' => ['type' => 'string', 'length' => 20, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'precision' => null, 'fixed' => null],
        'email' => ['type' => 'string', 'length' => 40, 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'precision' => null, 'fixed' => null],
        'contact_user' => ['type' => 'boolean', 'length' => null, 'null' => true, 'default' => null, 'precision' => null],
        'note_read' => ['type' => 'boolean', 'length' => null, 'null' => true, 'default' => null, 'precision' => null],
        '_indexes' => [
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
            'content' => 'test note 1',
            'created_on' => '2015-01-01',
            'project_role' => 'developer',
            'email' => 'test@test.com',
            'contact_user' => 1,
            'note_read' => 0,
        ],
        [
            'id' => 2,
            'content' => 'test note 2',
            'created_on' => '2015-01-02',
            'project_role' => 'senior_developer',
            'email' => 'testsendev@tessendev.com',
            'contact_user' => 0,
            'note_read' => 0,
        ],
    ];
}