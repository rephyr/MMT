<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\Http\ServerRequest;
use App\Controller\WeeklyhoursController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\WeeklyhoursController Test Case
 *
 * @uses \App\Controller\WeeklyhoursController
 */
class WeeklyhoursControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Weeklyhours',
        'app.Weeklyreports',
        'app.Members',
        'app.Projects',
        'app.Users',
    ];
    /**
     * @var \App\Controller\WeeklyhoursController
     */
    private $controller;
    /**
     * @var \App\Controller\WeeklyhoursController
     */
    private $Weeklyhours;
    public function setUp(): void
    {   
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $this->session([
            'Auth.User' => [
                'id' => 1,
                'role' => 'admin'
            ],
            'selected_project' => [
                'id' => 1
            ],
            'current_weeklyreport' => [
                'id' => 1
            ],
            'is_admin' => true,
        ]);
        $this->controller = new WeeklyhoursController(new ServerRequest(), null, 'Weeklyhours');
    }

    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\WeeklyhoursController::index()
     */
    public function testIndex(): void
    {
        $this->get('/weeklyhours/index');

        $this->assertResponseOk();

        $weeklyhours = $this->viewVariable('weeklyhours');
        $this->assertNotEmpty($weeklyhours);

        $memberlist = $this->viewVariable('memberlist');
        $this->assertNotEmpty($memberlist);

        $expected = [
            [
                'id' => 1,
                'weeklyreport_id' => 1,
                'member_id' => 1,
                'duration' => 1,
            ],
        ];

        $actual = array_map(function ($weeklyhour) {
            return [
                'id' => $weeklyhour->id,
                'weeklyreport_id' => $weeklyhour->weeklyreport_id,
                'member_id' => $weeklyhour->member_id,
                'duration' => $weeklyhour->duration,
            ];
        }, $weeklyhours->toArray());

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\WeeklyhoursController::view()
     */
    public function testView(): void
    {
        $this->get('/weeklyhours/view/1');
    
        $this->assertResponseOk();
    
        $weeklyhour = $this->viewVariable('weeklyhour');
        $this->assertNotEmpty($weeklyhour);
        $expected = [
            'id' => 1,
            'weeklyreport_id' => 1,
            'member_id' => 1,
            'duration' => 1.0,
            'member' => [
                'id' => 1,
                'member_name' => 'First Last - 1', // member_name is a virtual field
            ],
        ];
        // extract the member field from the weeklyhour object
        $actual = [
            'id' => $weeklyhour->id,
            'weeklyreport_id' => $weeklyhour->weeklyreport_id,
            'member_id' => $weeklyhour->member_id,
            'duration' => $weeklyhour->duration,
            'member' => [
                'id' => $weeklyhour->member->id,
                'member_name' => $weeklyhour->member->member_name,
            ],
        ];
    
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test add method with valid data
     *
     * @return void
     * @uses \App\Controller\WeeklyhoursController::add()
     */
    public function testAddValid(): void
    {
        // Test adding a valid weeklyhour
        $data = [
            'weeklyreport_id' => 1,
            'member_id' => 2, // Correct member_id
            'duration' => 5.1 // Correct duration
        ];
        $this->post('/weeklyhours/add', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Weeklyhours', 'action' => 'index']);
        $this->assertFlashMessage('The weeklyhour has been saved.');
    }
    /**
     * Test add method with invalid data
     *
     * @return void
     * @uses \App\Controller\WeeklyhoursController::add()
     */
    public function testAddInvalid(): void
    {
        // Test adding an invalid weeklyhour
        $data = [
            'weeklyreport_id' => 1,
            'member_id' => 1,
            'duration' => null // invalid data
        ];
        $this->post('/weeklyhours/add', $data);
        $this->assertResponseSuccess();
        $this->assertNoRedirect();
        $this->assertFlashMessage('The weeklyhour could not be saved. Please, try again.');
    }
    
    // /**
    //  * Test addmultiple method
    //  *
    //  * @return void
    //  * @uses \App\Controller\WeeklyhoursController::addmultiple()
    //  */
    // public function testAddmultiple(): void
    // {
    //     $this->markTestIncomplete('Not implemented yet.');
    // }
    
    /**
     * Test edit method with valid data
     *
     * @return void
     * @uses \App\Controller\WeeklyhoursController::edit()
     */
    public function testEditValid(): void
    {
        // clear tables to avoid conflicts
        $weeklyhoursTable = $this->getTableLocator()->get('Weeklyhours');
        $weeklyhoursTable->deleteAll([]);

        $weeklyreportsTable = $this->getTableLocator()->get('Weeklyreports');
        $weeklyreport = $weeklyreportsTable->newEntity([
            'id' => 1,
            'project_id' => 1,
            'title' => 'Lorem ipsum dolor sit amet',
            'week' => 2,  
            'year' => 2015, 
            'problems' => 'Some reported issues',
            'meetings' => 'Meeting details here',
            'additional' => 'Additional notes',
            'created_on' => '2015-01-01',
            'updated_on' => '2015-01-01',
            'created_by' => 1,
            'updated_by' => 1
        ]);


        $result = $weeklyreportsTable->save($weeklyreport);

        $this->assertNotEmpty($weeklyreport->id, 'The weeklyreport entity should have an id after being saved.');

        $membersTable = $this->getTableLocator()->get('Members');
        $member = $membersTable->newEntity([
            'user_id' => 1,
            'project_id' => 1,
            'project_role' => 'developer',
            'starting_date' => '2025-01-01',
            'ending_date' => null,
            'target_hours' => 40,
        ]);
        $membersTable->save($member);

        $this->assertNotEmpty($member->id, 'The member entity should have an id after being saved.');

        $weeklyhour = $weeklyhoursTable->newEntity([
            'weeklyreport_id' => $weeklyreport->id,
            'member_id' => $member->id,
            'duration' => 5.0
        ]);

        // attempt to save the weeklyhour
        $result = $weeklyhoursTable->save($weeklyhour);
        $this->assertNotEmpty($weeklyhour->id, 'The weeklyhour entity should have an id after being saved.');

        // test editing the weeklyhour
        $data = [
            'duration' => 6.0
        ];
        $this->post('/weeklyhours/edit/' . $weeklyhour->id, $data);
        $this->assertResponseSuccess();

        $updatedWeeklyhour = $weeklyhoursTable->get($weeklyhour->id);
        $this->assertEquals(6.0, $updatedWeeklyhour->duration);

        $this->assertFlashMessage('The weeklyhour has been saved.');
    }
    public function testEditInvalidId(): void
    {
        // Test editing a non-existent weeklyhour
        $this->post('/weeklyhours/edit/999', ['duration' => 6.0]);
        $this->assertResponseCode(404);
    }

    public function testEditInvalidData(): void
    {
        // needed for some reason
        $weeklyreportsTable = $this->getTableLocator()->get('Weeklyreports');
        $weeklyreport = $weeklyreportsTable->newEntity([
            'id' => 1,
            'project_id' => 1,
            'title' => 'Lorem ipsum dolor sit amet',
            'week' => 2,  
            'year' => 2015, 
            'problems' => 'Some reported issues',
            'meetings' => 'Meeting details here',
            'additional' => 'Additional notes',
            'created_on' => '2015-01-01',
            'updated_on' => '2015-01-01',
            'created_by' => 1,
            'updated_by' => 1
        ]);
        $weeklyreportsTable->save($weeklyreport);
        // mock for member
        $membersTable = $this->getTableLocator()->get('Members');
        $member = $membersTable->newEntity([
            'user_id' => 1,
            'project_id' => 1,
            'project_role' => 'developer',
            'starting_date' => '2023-01-01',
            'ending_date' => null,
            'target_hours' => 40,
        ]);
        $membersTable->save($member);

        // mock for weeklyhour
        $weeklyhoursTable = $this->getTableLocator()->get('Weeklyhours');
        $weeklyhour = $weeklyhoursTable->newEntity([
            'weeklyreport_id' => $weeklyreport->id,
            'member_id' => $member->id,
            'duration' => 5.0
        ]);

        $result = $weeklyhoursTable->save($weeklyhour);

        $this->assertNotEmpty($weeklyhour->id, 'The weeklyhour entity should have an id after being saved.');

        $data = [
            'duration' => null // invalid data
        ];
        $this->post('/weeklyhours/edit/' . $weeklyhour->id, $data);
        $this->assertResponseSuccess();

        $updatedWeeklyhour = $weeklyhoursTable->get($weeklyhour->id);
        $this->assertEquals(5.0, $updatedWeeklyhour->duration);

        $this->assertFlashMessage('The weeklyhour could not be saved. Please, try again.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\WeeklyhoursController::delete()
     */
    public function testDelete(): void
    {
        $weeklyhoursTable = $this->getTableLocator()->get('Weeklyhours');
        $weeklyhoursTable->deleteAll([]);

        // ensure that the weeklyreport and member entities exist
        $weeklyreportsTable = $this->getTableLocator()->get('Weeklyreports');
        $weeklyreport = $weeklyreportsTable->newEntity([
            'id' => 1,
            'project_id' => 1,
            'title' => 'Lorem ipsum dolor sit amet',
            'week' => 2,  
            'year' => 2015, 
            'problems' => 'Some reported issues',
            'meetings' => 'Meeting details here',
            'additional' => 'Additional notes',
            'created_on' => '2015-01-01',
            'updated_on' => '2015-01-01',
            'created_by' => 1,
            'updated_by' => 1
        ]);
        $weeklyreportsTable->save($weeklyreport);

        $this->assertNotEmpty($weeklyreport->id, 'The weeklyreport entity should have an id after being saved.');

        $membersTable = $this->getTableLocator()->get('Members');
        $member = $membersTable->newEntity([
            'user_id' => 1,
            'project_id' => 1,
            'project_role' => 'developer',
            'starting_date' => '2023-01-01',
            'ending_date' => null,
            'target_hours' => 40,
        ]);
        $membersTable->save($member);

        $this->assertNotEmpty($member->id, 'The member entity should have an id after being saved.');
        // create a weeklyhour entity
        $weeklyhour = $weeklyhoursTable->newEntity([
            'weeklyreport_id' => $weeklyreport->id,
            'member_id' => $member->id,
            'duration' => 5.0
        ]);
        $weeklyhoursTable->save($weeklyhour);

        $this->assertNotEmpty($weeklyhour->id, 'The weeklyhour entity should have an id after being saved.');

        $this->post('/weeklyhours/delete/' . $weeklyhour->id);
        $this->assertResponseSuccess();

        // verify that the weeklyhour was deleted
        $deletedWeeklyhour = $weeklyhoursTable->find()->where(['id' => $weeklyhour->id])->first();
        $this->assertNull($deletedWeeklyhour, 'The weeklyhour entity should be deleted.');

        $this->assertFlashMessage('The weeklyhour has been deleted.');

        $this->assertRedirect(['action' => 'index']);
    }

    public function testIsAuthorizedAdmin(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'add');
        $this->controller->setRequest($request);
        $result = $this->controller->isAuthorized(['role' => 'admin']);
        $this->assertTrue($result, "Admin should be authorized to add weeklyhours.");

        $request = $request->withParam('action', 'delete');
        $this->controller->setRequest($request);
        $result = $this->controller->isAuthorized(['role' => 'admin']);
        $this->assertTrue($result, "Admin should be authorized to delete weeklyhours.");

        $request = $request->withParam('action', 'edit');
        $this->controller->setRequest($request);
        $result = $this->controller->isAuthorized(['role' => 'admin']);
        $this->assertTrue($result, "Admin should be authorized to edit weeklyhours.");
    }

    public function testIsAuthorizedNonAdmin(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'add');
        $this->controller->setRequest($request);
        $result = $this->controller->isAuthorized(['role' => 'developer']);
        $this->assertFalse($result, "Developer should not be authorized to add weeklyhours.");

        $request = $request->withParam('action', 'delete');
        $this->controller->setRequest($request);
        $result = $this->controller->isAuthorized(['role' => 'developer']);
        $this->assertFalse($result, "Developer should not be authorized to delete weeklyhours.");
    }

    public function testIsAuthorizedParentCallForOtherActions(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'view');
        $this->controller->setRequest($request);
        $result = $this->controller->isAuthorized(['role' => 'developer']);
        $this->assertTrue($result, "Developer should be authorized to view weeklyhours.");
    }
}
