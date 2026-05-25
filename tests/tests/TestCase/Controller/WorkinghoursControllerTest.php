<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\WorkinghoursController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\WorkinghoursController Test Case
 *
 * @uses \App\Controller\WorkinghoursController
 */
class WorkinghoursControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Workinghours',
        'app.Members',
        'app.Weeklyreports',
        'app.Worktypes',
        'app.Users',
        'app.Projects',
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
     * @uses \App\Controller\WorkinghoursController::index()
     */
    public function testIndex(): void
    {
        // Test as an authenticated user
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'testuser@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 1,
            'selected_project_memberid' => 1
        ]);
        $this->get('/workinghours');
        
        $this->assertResponseOk();
        $this->assertResponseCode(200);
        $this->assertContentType('text/html');
        $this->assertTemplate('index');

        $this->assertNotEmpty($this->viewVariable('workinghours'));
        $this->assertNotEmpty($this->viewVariable('memberlist'));
        $this->assertNotEmpty($this->viewVariable('members'));

        // Test that workinghours are filtered by project
        $workinghours = $this->viewVariable('workinghours');
        foreach ($workinghours as $workinghour) {
            $this->assertEquals(1, $workinghour->member->project_id);
        }

        // Test with invalid project ID
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'testuser@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 999],
            'selected_project_role' => 1,
            'selected_project_memberid' => 1
        ]);

        $this->get('/workinghours');
        $this->assertResponseOk();
        $this->assertEmpty($this->viewVariable('workinghours'));
    }

    /**
     * Test tasks method
     *
     * @return void
     * @uses \App\Controller\WorkinghoursController::tasks()
     */
    public function testTasks(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Test as developer viewing their own tasks
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'testuser@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'developer',
            'selected_project_memberid' => 1,
            'is_admin' => false
        ]);

        $this->get('/workinghours/tasks/1');
        
        $this->assertResponseOk();
        $this->assertResponseCode(200);
        $this->assertContentType('text/html');

        $this->assertNotEmpty($this->viewVariable('workinghours'));
        $this->assertNotEmpty($this->viewVariable('memberlist'));
        
        $workinghours = $this->viewVariable('workinghours');
        foreach ($workinghours as $workinghour) {
            $this->assertEquals(1, $workinghour->member_id);
            $this->assertEquals(1, $workinghour->member->project_id);
        }

        $this->get('/workinghours/tasks/1?page=1');
        $this->assertResponseOk();
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\WorkinghoursController::view()
     */
    public function testView(): void
    {
        // Test viewing as the creator of the workinghour
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'testuser@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'developer',
            'selected_project_memberid' => 1,
            'is_admin' => false
        ]);
        $this->get('/workinghours/view/1001');
        
        $this->assertResponseSuccess();
        $this->assertResponseContains('View logged task');
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Edit logged time'); // Should see edit button

        // Test viewing as different developer (not creator)
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 2,
                    'email' => 'developer@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'developer',
            'selected_project_memberid' => 2,
            'is_admin' => false
        ]);

        $this->get('/workinghours/view/1001');
        
        $this->assertResponseSuccess();
        $this->assertResponseContains('View logged task');
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseNotContains('Edit logged time'); // Should not see edit button

        // Test viewing as supervisor
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'testuser@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'supervisor',
            'selected_project_memberid' => 1,
            'is_admin' => false
        ]);

        $this->get('/workinghours/view/1001');
        
        $this->assertResponseSuccess();
        $this->assertResponseContains('View logged task');
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Edit logged time');  // Should see edit button

        // Test viewing as senior developer
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'testuser@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'senior_developer',
            'selected_project_memberid' => 1,
            'is_admin' => false
        ]);

        $this->get('/workinghours/view/1001');
        
        $this->assertResponseSuccess();
        $this->assertResponseContains('View logged task');
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Edit logged time'); // Should see edit button

        // 5. Test viewing as admin
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'testuser@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'developer',
            'selected_project_memberid' => 1,
            'is_admin' => true
        ]);

        $this->get('/workinghours/view/1001');
        
        $this->assertResponseSuccess();
        $this->assertResponseContains('View logged task');
        $this->assertResponseContains('Lorem ipsum dolor sit amet');
        $this->assertResponseContains('Edit logged time'); // Should see edit button

        // Test viewing non-existent workinghour
        $this->get('/workinghours/view/99999');
        $this->assertResponseError();
    }

    /**
     * Test add method
     *
     * @return void
     * @uses \App\Controller\WorkinghoursController::add()
     */
    public function testAdd(): void
    {

        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'testuser@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'developer',
            'selected_project_memberid' => 1,
            'is_admin' => false
        ]);

        $data = [
            'date' => [
            'year' => '2024',
            'month' => '10',
            'day' => '29'
            ],
            'member_id' => 1,
            'description' => 'test description',
            'duration' => 2.5,
            'worktype_id' => 1001
        ];

        $this->post('/workinghours/add', $data);
        $this->assertResponseSuccess();

        $workinghours = $this->getTableLocator()->get('Workinghours');
    
        $query = $workinghours->find()
            ->where([
                'Workinghours.description' => 'test description',
                'Workinghours.member_id' => 1
            ])
            ->contain(['Members', 'Worktypes']);

        $result = $query->first();
        $this->assertNotEmpty($result);
        $this->assertEquals('test description', $result->description);
        $this->assertEquals(1, $result->member_id);
        $this->assertEquals(1001, $result->worktype_id);
        $this->assertEquals(2.5, $result->duration);
        $this->assertEquals('2024-10-29', $result->date->format('Y-m-d'));

        $this->assertFlashMessage('The workinghour has been saved.');        
    }

    /**
     * Test adddev method
     *
     * @return void
     * @uses \App\Controller\WorkinghoursController::adddev()
     */
    public function testAdddev(): void
    {
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'testuser@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'supervisor',
            'selected_project_memberid' => 1,
            'is_admin' => false
        ]);

        $data = [
            'date' => [
            'year' => '2024',
            'month' => '10',
            'day' => '29'
            ],
            'member_id' => 2,
            'description' => 'add_dev',
            'duration' => 2.5,
            'worktype_id' => 1001
        ];

        $this->post('/workinghours/adddev', $data);
        $this->assertResponseSuccess();

        $workinghours = $this->getTableLocator()->get('Workinghours');
    
        $query = $workinghours->find()
            ->where([
                'Workinghours.description' => 'add_dev',
                'Workinghours.member_id' => 2
            ])
            ->contain(['Members', 'Worktypes']);

        $result = $query->first();
        $this->assertNotEmpty($result);
        $this->assertEquals('add_dev', $result->description);
        $this->assertEquals(2, $result->member_id);
        $this->assertEquals(1001, $result->worktype_id);
        $this->assertEquals(2.5, $result->duration);
        $this->assertEquals('2024-10-29', $result->date->format('Y-m-d'));

        $this->assertFlashMessage('The workinghour has been saved.');       
    }

    /**
     * Test adddev method with unauthorized user
     *
     * @return void
     * @uses \App\Controller\WorkinghoursController::adddev()
     */
    public function testAdddevUnauthorized(): void
        {
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'testuser@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'developer',
            'selected_project_memberid' => 1,
            'is_admin' => false
        ]);

        $this->get('/workinghours/adddev');
        $this->assertRedirect();

        $data = [
            'member_id' => 2,
            'date' => [
                'year' => '2024',
                'month' => '01',
                'day' => '01'
            ],
            'description' => 'unauthorized_attempt',
            'duration' => 2.5,
            'worktype_id' => 1001
        ];

        $this->post('/workinghours/adddev', $data);
        
        $this->assertRedirect();
        
        // Verify no data was saved
        $workinghours = $this->getTableLocator()->get('Workinghours');
        $query = $workinghours->find()
            ->where([
                'Workinghours.description' => 'unauthorized_attempt',
                'Workinghours.member_id' => 2
            ]);

        $this->assertEquals(0, $query->count());
        
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\WorkinghoursController::edit()
     */
    public function testEdit(): void
    {
        // Test edit as developer editing their own hours
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'testuser@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'developer',
            'selected_project_memberid' => 1
        ]);
        $data = [
            'date' => [
                'year' => '2024',
                'month' => '10',
                'day' => '29'
            ],
            'member_id' => 1,
            'description' => 'original description',
            'duration' => 2.5,
            'worktype_id' => 1001
        ];

        $this->post('/workinghours/add', $data);
        $this->assertResponseSuccess();

        $workinghours = $this->getTableLocator()->get('Workinghours');
        $workinghour = $workinghours->find()
            ->where([
                'description' => 'original description',
                'member_id' => 1
            ])
            ->first();

        $editData = [
            'date' => [
                'year' => '2024',
                'month' => '10',
                'day' => '30'
            ],
            'member_id' => 1,
            'description' => 'edited description',
            'duration' => 3.5,
            'worktype_id' => 1001
        ];

        $this->post('/workinghours/edit/' . $workinghour->id, $editData);
        $this->assertResponseSuccess();

        $updated = $workinghours->get($workinghour->id, [
            'contain' => ['Members', 'Worktypes']
        ]);

        $this->assertEquals('edited description', $updated->description);
        $this->assertEquals(3.5, $updated->duration);
        $this->assertEquals('2024-10-30', $updated->date->format('Y-m-d'));
        $this->assertFlashMessage('The workinghour has been saved.');

        // Test unauthorized edit (dev trying to edit another dev's hours)
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 2,
                    'email' => 'developer@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'developer',
            'selected_project_memberid' => 2
        ]);

        $unauthorizedEdit = [
            'date' => [
                'year' => '2024',
                'month' => '10',
                'day' => '31'
            ],
            'member_id' => 1,
            'description' => 'unauthorized edit',
            'duration' => 1.0,
            'worktype_id' => 1001
        ];

        $this->post('/workinghours/edit/' . $workinghour->id, $unauthorizedEdit);
        $this->assertResponseSuccess();
        $this->assertRedirect();

        $stillSame = $workinghours->get($workinghour->id);
        $this->assertEquals('edited description', $stillSame->description);
        $this->assertEquals(3.5, $stillSame->duration);
        $this->assertEquals('2024-10-30', $stillSame->date->format('Y-m-d'));

        // Test edit as supervisor
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'testuser@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'supervisor',
            'selected_project_memberid' => 1
        ]);

        $supervisorEdit = [
            'date' => [
                'year' => '2024',
                'month' => '11',
                'day' => '01'
            ],
            'member_id' => 1,
            'description' => 'supervisor edit',
            'duration' => 4.0,
            'worktype_id' => 1001
        ];

        $this->post('/workinghours/edit/' . $workinghour->id, $supervisorEdit);
        $this->assertResponseSuccess();

        $supervisorUpdated = $workinghours->get($workinghour->id);
        $this->assertEquals('supervisor edit', $supervisorUpdated->description);
        $this->assertEquals(4.0, $supervisorUpdated->duration);
        $this->assertEquals('2024-11-01', $supervisorUpdated->date->format('Y-m-d'));
        $this->assertFlashMessage('The workinghour has been saved.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\WorkinghoursController::delete()
     */
    public function testDelete(): void
    {   
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'testuser@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'developer',
            'selected_project_memberid' => 1,
            'is_admin' => false
        ]);

        $data = [
            'date' => [
            'year' => '2024',
            'month' => '10',
            'day' => '29'
            ],
            'member_id' => 1,
            'description' => 'test description',
            'duration' => 2.5,
            'worktype_id' => 1001
        ];

        $this->post('/workinghours/add', $data);
        $this->assertResponseSuccess();

        $workinghours = $this->getTableLocator()->get('Workinghours');
    
        $query = $workinghours->find()
            ->where([
                'Workinghours.description' => 'test description',
                'Workinghours.member_id' => 1
            ])
            ->contain(['Members', 'Worktypes']);

        $result = $query->first();
        $this->assertNotEmpty($result);
        $this->assertFlashMessage('The workinghour has been saved.');

        $this->post('/workinghours/delete/' . $result->id);

        $query = $workinghours->find()
            ->where([
                'Workinghours.description' => 'test description',
                'Workinghours.member_id' => 1
            ])
            ->contain(['Members', 'Worktypes']);

        $result = $query->first();
        $this->assertEmpty($result);

        $this->assertFlashMessage('The workinghour has been deleted.');
    }

    /**
     * Test export method
     *
     * @return void
     * @uses \App\Controller\WorkinghoursController::export()
     */
    public function testExport(): void
    {
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'testuser@example.com',
                    'role' => 'admin',
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'admin', 
            'selected_project_memberid' => 1,
            'is_admin' => true
        ]);

        $this->configRequest([
            'accept' => 'text/csv'
        ]);

        $this->get('/workinghours/export');
        $this->assertResponseOk();
        $headers = $this->_response->getHeaders();
        
        $this->assertStringContainsString(
            'text/csv',
            strtolower($headers['Content-Type'][0]),
            'Content-Type header should contain text/csv'
        );
        
        $this->assertStringContainsString(
            'attachment; filename="workinghours_export.csv"',
            $headers['Content-Disposition'][0],
            'Content-Disposition header should specify CSV file download'
        );

        $csv = $this->_response->getBody()->__toString();
        
        $lines = explode("\n", trim($csv));
        $this->assertEquals('project_name,member_id,description,sum', $lines[0]);
        $data = array_map('str_getcsv', $lines);
        array_shift($data);

        // Check each row
        foreach ($data as $row) {
            if (empty($row[0])) {
                continue;
            }
            $this->assertCount(4, $row);
            $this->assertEquals('TestProject', $row[0]);
            $this->assertIsNumeric($row[1]);
            $this->assertNotEmpty($row[2]);
            $this->assertIsNumeric($row[3]);
        }
    }

    /**
     * Test isAuthorized method
     *
     * @return void
     * @uses \App\Controller\WorkinghoursController::isAuthorized()
     */
    public function testIsAuthorized(): void
    {       
        // Test addlate (should be denied for supervisors)
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 3,
                    'email' => 'supervisor@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'supervisor',
            'selected_project_memberid' => 3,
            'is_admin' => false
        ]);

        $this->get('/workinghours/addlate');
        $this->assertResponseCode(302);
        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);

        // Test tasks access for different roles
        // Test as client (should be allowed)
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 5,
                    'email' => 'client@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'client',
            'selected_project_memberid' => 5,
            'is_admin' => false
        ]);

        $this->get('/workinghours/tasks/1');
        $this->assertResponseOk();

        // Test as non-member (should be denied)
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 6,
                    'email' => 'nonmember@example.com',
                    'role' => 1
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'notmember',
            'selected_project_memberid' => 6,
            'is_admin' => false
        ]);

        $this->get('/workinghours/tasks/1');
        $this->assertResponseCode(302);
        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);
    }

    /**
     * Test addlate method
     *
     * @return void
     * @uses \App\Controller\WorkinghoursController::addlate()
     */
    public function testAddlate(): void
    {
        $this->enableRetainFlashMessages();
        // admins can use addlate
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'admin@admin.com',
                    'role' => 'admin'
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'developer',
            'selected_project_memberid' => 1,
            'is_admin' => true
        ]);

        // valid data
        $data = [
            'date' => [
                'year' => '2015',
                'month' => '01',
                'day' => '01'
            ],
            'member_id' => 1,
            'description' => 'testDesc',
            'duration' => 1,
            'worktype_id' => 1001 // don't know what this means but it was in the fixture
        ];
        $this->post('/workinghours/addlate', $data);

        $this->assertResponseSuccess();
        $this->assertFlashMessage('The workinghour has been saved.');

        // see 
        $workinghours = $this->getTableLocator()->get('Workinghours');
        $query = $workinghours->find()
            ->where([
                'Workinghours.description' => 'testDesc',
                'Workinghours.member_id' => 1
            ])
            ->contain(['Members', 'Worktypes']);

        $result = $query->first();
        $this->assertNotEmpty($result);
        $this->assertEquals('testDesc', $result->description);
        $this->assertEquals(1, $result->member_id);
        $this->assertEquals(1001, $result->worktype_id);
        $this->assertEquals(1, $result->duration);
        $this->assertEquals('2015-01-01', $result->date->format('Y-m-d'));
    }

    /**
     * Test addlate method with validation errors
     *
     * @return void
     * @uses \App\Controller\WorkinghoursController::addlate()
     */
    public function testAddlateValidationErrors(): void
    {
        // admins can use addlate
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 1,
                    'email' => 'test@test.com',
                    'role' => 'admin'
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'admin',
            'selected_project_memberid' => 1,
            'is_admin' => true
        ]);
        // invalid data
        $data = [
            'date' => '',
            'member_id' => 1,
            'description' => '',
            'duration' => '',
            'worktype_id' => '' 
        ];

        $this->post('/workinghours/addlate', $data);
        $this->assertResponseContains('The workinghour could not be saved. Please, try again.');
        $this->assertNoRedirect();

        $workinghours = $this->getTableLocator()->get('Workinghours');
        // should not find any workinghours with empty description
        $query = $workinghours->find()
            ->where([
                'Workinghours.description' => '',
                'Workinghours.member_id' => 1
            ]);
        $this->assertEquals(0, $query->count());
    }

    /**
     * Test addlate method with unauthorized user
     *
     * @return void
     * @uses \App\Controller\WorkinghoursController::addlate()
     */
    public function testAddlateUnauthorized(): void
    {
        // non-admin
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 2,
                    'email' => 'unauthorizeduser@test.com',
                    'role' => 'developer'
                ]
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'developer',
            'selected_project_memberid' => 2,
            'is_admin' => false
        ]);
        // valid data
        $data = [
            'date' => [
                'year' => '2015',
                'month' => '1',
                'day' => '1'
            ],
            'member_id' => 1,
            'description' => 'Lorem ipsum dolor sit amet',
            'duration' => 1,
            'worktype_id' => 1001 //don't know what this means but it was in the fixture
        ];

        $this->post('/workinghours/addlate', $data);
        // AppController redirects to login page if unauthorized
        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);

        $workinghours = $this->getTableLocator()->get('Workinghours');
        $query = $workinghours->find()
            ->where([
                'Workinghours.description' => 'Lorem ipsum dolor sit amet',
                'Workinghours.member_id' => 2
            ]);

        $this->assertEquals(0, $query->count());
    }
    
}