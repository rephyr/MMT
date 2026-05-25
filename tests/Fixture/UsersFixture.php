<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Cake\Auth\DefaultPasswordHasher;

/**
 * UsersFixture
 *
 */
class UsersFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'integer', 'length' => 10, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
        'email' => ['type' => 'string', 'length' => 40, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'password' => ['type' => 'string', 'length' => 20, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'first_name' => ['type' => 'string', 'length' => 20, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'last_name' => ['type' => 'string', 'length' => 20, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'role' => ['type' => 'integer', 'length' => 2, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'password_key' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
            'email' => ['type' => 'unique', 'columns' => ['email'], 'length' => []],
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
    public $records = [];

    public function init(): void
    {
        $this->records = [
            [
                'email' => 'testuser@example.com',
                'password' => (new DefaultPasswordHasher)->hash('whatever'),
                'first_name' => 'First',
                'last_name' => 'Last',
                'role' => 1,
                'password_key' => null,
            ],
            [
                'email' => 'existinguser@example.com',
                'password' => (new DefaultPasswordHasher)->hash('password123'),
                'first_name' => 'Existing',
                'last_name' => 'User',
                'role' => 1,
                'password_key' => 'validpasswordkey',
            ],
            [
                'email' => 'developer@example.com',
                'password' => (new DefaultPasswordHasher)->hash('whatever'),
                'first_name' => 'Dev',
                'last_name' => 'User',
                'role' => 1,
                'password_key' => null,
            ],
        ];
        parent::init();
    }
}
