<?php
namespace App\Test\TestCase\Controller;

use App\Controller\MembersController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;

class MembersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected $fixtures = [
        'app.Users',
        'app.Members',
        'app.Projects'
    ];

    private $controller;

    public function setUp(): void {
        parent::setUp();

        // Set auth
        $this->session([
            'Auth' => [
                'User' => [
                    'role' => 'admin',
                    'id' => 1,
                ]
            ]
        ]);
        $this->session(['is_admin' => true]);
        $this->session(['selected_project' => ['id' => 1]]);
    }

    public function testAddNonexistant() {
        $this->session(['selected_project' => ['id' => 1]]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'email' => 'does_not_exist',
            'project_role' => 'senior_developer',
            'target_hours' => "130",
        ];

        $this->post('/members/add', $data);

        $this->assertResponseError();
    }

    public function testAddExisting() {
        $this->session(['selected_project' => ['id' => 1]]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'email' => 'testuser@example.com',
            'project_role' => 'senior_developer',
            'target_hours' => "130",
        ];

        $this->post('/members/add', $data);

        $this->assertResponseSuccess();
    }
    
    /**
     * Test add method for successfully adding a new member
     *
     * @return void
     * @uses \App\Controller\MembersController::add()
     */
    public function testAddNewMember(): void
    {
        $this->enableRetainFlashMessages();

        $this->session([
            'Auth' => [
                'User' => [
                    'role' => 'admin',
                    'id' => 1,
                ]
            ],
            'selected_project' => [
                'id' => 1,
                'created_on' => '2024-11-14',
                'finished_date' => '2024-12-31'
            ],
            'selected_project_role' => 'senior_developer',
            'is_admin' => true
        ]);

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Create a user with the email 'newuser@example.com'
        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->newEntity([
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'first_name' => 'New',
            'last_name' => 'User',
            'role' => 'user'
        ]);

        $this->assertNotFalse($usersTable->save($user), 'User should be saved successfully');

        // Test adding a new member
        $data = [
            'email' => 'newuser@example.com',
            'project_role' => 'developer',
            'target_hours' => "130",
        ];
        $this->post('/members/add', $data);

        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Members', 'action' => 'index']);
        $this->assertFlashMessage('The member has been saved.');

        // Verify the member was added to the database
        $membersTable = TableRegistry::getTableLocator()->get('Members');
        $member = $membersTable->find()->where(['user_id' => $user->id, 'project_id' => 1])->first();
        $this->assertNotEmpty($member, 'Member should be added to the database');
        $this->assertEquals('developer', $member->project_role);
        $this->assertEquals(130, $member->target_hours);
    }

    /**
     * Test edit method for successfully editing an existing member
     *
     * @return void
     * @uses \App\Controller\MembersController::edit()
     */
    public function testEdit(): void
    {
        // Ensure the member exists in the database
        $membersTable = TableRegistry::getTableLocator()->get('Members');
        $member = $membersTable->newEntity([
            'user_id' => 1,
            'project_id' => 1,
            'project_role' => 'developer',
            'target_hours' => 130
        ]);
        $membersTable->save($member);

        // Test editing the member
        $data = [
            'project_role' => 'senior_developer',
            'target_hours' => 150
        ];
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/members/edit/' . $member->id, $data);

        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Members', 'action' => 'index']);
        $this->assertFlashMessage('The member has been saved.');

        // Verify the member was updated
        $updatedMember = $membersTable->get($member->id);
        $this->assertEquals('senior_developer', $updatedMember->project_role);
        $this->assertEquals(150, $updatedMember->target_hours);
    }

    /**
     * Test delete method for successfully deleting an existing member
     *
     * @return void
     * @uses \App\Controller\MembersController::delete()
     */
    public function testDelete(): void
    {
        // Ensure the member exists in the database
        $membersTable = TableRegistry::getTableLocator()->get('Members');
        $member = $membersTable->newEntity([
            'user_id' => 1,
            'project_id' => 1,
            'project_role' => 'developer',
            'target_hours' => 130
        ]);
        $membersTable->save($member);

        // Test deleting the member
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/members/delete/' . $member->id);

        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Members', 'action' => 'index']);
        $this->assertFlashMessage('The member has been deleted.');

        // Verify the member was deleted
        $deletedMember = $membersTable->find()->where(['id' => $member->id])->first();
        $this->assertEmpty($deletedMember);
    }

    /**
     * Test delete method for attempting to delete a non-existent member
     *
     * @return void
     * @uses \App\Controller\MembersController::delete()
     */
    public function testDeleteNonExistentMember(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/members/delete/99999'); // Non-existent member ID

        $this->assertResponseError();
    }

    /**
     * Test isAuthorized method for admin role
     *
     * @return void
     * @uses \App\Controller\MembersController::isAuthorized()
     */
    public function testIsAuthorizedAdmin(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'anyAction');
        $request->getSession()->write([
            'Auth.User' => ['id' => 1, 'role' => 'admin'],
        ]);
        $this->controller = new MembersController($request);
        $result = $this->controller->isAuthorized(['role' => 'admin']);
        $this->assertTrue($result, "Admin should be authorized for any action.");
    }

    /**
     * Test isAuthorized method for senior developer role
     *
     * @return void
     * @uses \App\Controller\MembersController::isAuthorized()
     */
    public function testIsAuthorizedSeniorDeveloper(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'add');
        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
            'selected_project_role' => 'senior_developer',
        ]);
        $this->controller = new MembersController($request);
        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertTrue($result, "Senior developer should be authorized to add members.");
    }

    /**
     * Test isAuthorized method for developer role
     *
     * @return void
     * @uses \App\Controller\MembersController::isAuthorized()
     */
    public function testIsAuthorizedDeveloper(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'add');
        $request->getSession()->write([
            'Auth.User' => ['id' => 3, 'role' => 'user'],
            'selected_project_role' => 'developer',
        ]);
        $this->controller = new MembersController($request);
        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertFalse($result, "Developer should not be authorized to add members.");
    }

    /**
     * Test isAuthorized method for guest role
     *
     * @return void
     * @uses \App\Controller\MembersController::isAuthorized()
     */
    public function testIsAuthorizedGuest(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'add');
        $request->getSession()->write([
            'Auth.User' => ['id' => 4, 'role' => 'user'],
            'selected_project_role' => 'guest',
        ]);
        $this->controller = new MembersController($request);
        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertFalse($result, "Guest should not be authorized to add members.");
    }
}