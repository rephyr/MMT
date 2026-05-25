<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\WorktypesController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\WorktypesController Test Case
 *
 * @uses \App\Controller\WorktypesController
 */
class WorktypesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Worktypes',
        'app.Users',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\WorktypesController::index()
     */
    public function testIndex(): void
    {
        $this->session([
            'Auth.User' => [
                'id' => 1,
                'role' => 'admin'
            ],
            'is_admin' => true
        ]);

        $this->get('/worktypes');
        $this->assertResponseOk();
        $this->assertResponseCode(200);
        $this->assertContentType('text/html');
        $this->assertTemplate('index');
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\WorktypesController::view()
     */
    public function testView(): void
    {
        $this->session([
            'Auth.User' => [
                'id' => 1,
                'role' => 'admin'
            ],
            'is_admin' => true
        ]);

        $this->get('/worktypes/view/1001');
        $this->assertResponseOk();
        $this->assertResponseCode(200);
        $this->assertContentType('text/html');
        $this->assertTemplate('view');

        $worktype = $this->viewVariable('worktype');
        $this->assertEquals(1001, $worktype->id);
        $this->assertEquals('Lorem ipsum dolor sit amet', $worktype->description);
    }

    /**
     * Test add method
     *
     * @return void
     * @uses \App\Controller\WorktypesController::add()
     */
    public function testAdd(): void
    {
        $this->session([
            'Auth.User' => [
                'id' => 1,
                'role' => 'admin'
            ],
            'is_admin' => true
        ]);

        $data = [
            'id' => 1002,
            'description' => 'test description'
        ];

        $this->post('/worktypes/add', $data);
        $this->assertResponseSuccess();

        $worktypes = $this->getTableLocator()->get('Worktypes');
        $query = $worktypes->find()
            ->where([
                'Worktypes.id' => 1002,
                'Worktypes.description' => 'test description'
            ]);
        
        $result = $query->first();
        $this->assertNotEmpty($result);
        $this->assertEquals(1002, $result->id);
        $this->assertEquals('test description', $result->description);
        $this->assertFlashMessage('The worktype has been saved.');
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\WorktypesController::edit()
     */
    public function testEdit(): void
    {
        $this->session([
            'Auth.User' => [
                'id' => 1,
                'role' => 'admin'
            ],
            'is_admin' => true
        ]);

        $data = [
            'id' => 1002,
            'description' => 'test description'
        ];

        $this->post('/worktypes/add', $data);
        $this->assertResponseSuccess();

        $data = [
            'id' => 1002,
            'description' => 'test description edited'
        ];

        $this->post('/worktypes/edit/1002', $data);
        $this->assertResponseSuccess();
        $worktypes = $this->getTableLocator()->get('Worktypes');

        $query = $worktypes->find()
            ->where([
                'Worktypes.id' => 1002,
                'Worktypes.description' => 'test description edited'
            ]);
        
        $result = $query->first();
        $this->assertNotEmpty($result);
        $this->assertEquals(1002, $result->id);
        $this->assertEquals('test description edited', $result->description);
        $this->assertFlashMessage('The worktype has been saved.');

    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\WorktypesController::delete()
     */
    public function testDelete(): void
    {
        $this->session([
            'Auth.User' => [
                'id' => 1,
                'role' => 'admin'
            ],
            'is_admin' => true
        ]);

        $data = [
            'id' => 1002,
            'description' => 'test description'
        ];

        $this->post('/worktypes/add', $data);
        $this->assertResponseSuccess();


        $worktypes = $this->getTableLocator()->get('Worktypes');
        $query = $worktypes->find()
            ->where([
                'Worktypes.id' => 1002,
                'Worktypes.description' => 'test description'
            ]);

        $result = $query->first();
        $this->assertNotEmpty($result);
        
        $this->post('/worktypes/delete/1002');
        $this->assertResponseSuccess();

        $query = $worktypes->find()
            ->where([
                'Worktypes.id' => 1002,
                'Worktypes.description' => 'test description'
            ]);

        $result = $query->first();
        $this->assertEmpty($result);
        $this->assertFlashMessage('The worktype has been deleted.');
    }

    /**
     * Test isAuthorized method
     *
     * @return void
     * @uses \App\Controller\WorktypesController::isAuthorized()
     */
    public function testIsAuthorized(): void
    {
        $controller = new WorktypesController();
        $user_admin = [
            'id' => 1,
            'role' => 'admin'
        ];
        $result = $controller->isAuthorized($user_admin);
        $this->assertTrue($result);

        $user_developer = [
            'id' => 2,
            'role' => 'developer'
        ];
        $result_unauthorized = $controller->isAuthorized($user_developer);
        $this->assertFalse($result_unauthorized);
    }
}
