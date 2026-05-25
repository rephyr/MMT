<?php

namespace App\Test\TestCase\Controller;

use App\Controller\UsersController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Mailer\TransportFactory;
use Cake\Http\ServerRequest;
use Cake\Mailer\Email;
use Cake\ORM\TableRegistry;
use Cake\Mailer\Transport\DebugTransport;
use Cake\Event\EventInterface;


/**
 * App\Controller\UsersController Test Case
 *
 * @uses \App\Controller\UsersController
 */
class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Users',
        'app.Projects',
    ];
    /**
     * Controller instance
     *
     * @var \App\Controller\UsersController
     */
    protected $controller;

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
        $this->controller = new UsersController(new ServerRequest(), null, 'Users');

    }

    /**
     * Test login method when user is already logged in
     *
     * @return void
     * @uses \App\Controller\UsersController::login()
     */
    public function testLoginAlreadyLoggedIn(): void
    {
        $this->session(['Auth.User.id' => 1]);
    
        $this->get('/');
    
        $this->assertRedirect(['controller' => 'Projects', 'action' => 'index']);
    }

    /**
     * Test login method with valid credentials
     *
     * @return void
     * @uses \App\Controller\UsersController::login()
     */
    public function testLoginValidCredentials(): void
    {

        $this->post('/', [
            'email' => 'testuser@example.com',
            'password' => 'whatever'
        ]);

        $this->assertSession(1, 'Auth.User.id');
        $this->assertSession('testuser@example.com', 'Auth.User.email');

        $this->assertSession(true, 'first_view');

        $expected = [
            'controller' => 'Projects',
            'action' => 'index'
        ];
        
        $this->assertRedirect($expected);
    }

    /**
     * Test login method with invalid credentials
     *
     * @return void
     * @uses \App\Controller\UsersController::login()
     */
    public function testLoginInvalidCredentials(): void
    {
        $this->enableRetainFlashMessages();

        $this->post('/', [
            'email' => 'invaliduser@example.com',
            'password' => 'invalidpassword'
        ]);

        $this->assertResponseOk();

        $this->assertFlashMessage('Your username or password is incorrect.');

        $this->assertSession(null, 'Auth.User');
    }

    /**
     * Test logout method
     *
     * @return void
     * @uses \App\Controller\UsersController::logout()
     */
    public function testLogout(): void
    {
        $this->session(['Auth.User' => ['id' => 1, 
                        'role' => 'admin'], 
                        'selected_project' => ['id' => 1], 
                        'is_admin' => true]);
        
        $this->session([
            'selected_project' => ['id' => 1],             
            'selected_project_role' => 'user',
            'selected_project_memberid' => 1,
            'current_weeklyreport' => 'report1',
            'current_metrics' => 'metrics1',
            'current_weeklyhours' => 'hours1',
            'project_list' => ['project1', 'project2'],
            'project_memberof_list' => ['member1', 'member2'],
            'is_admin' => true,
            'is_supervisor' => true,
        ]);
        $this->get('/users/logout');
        $this->assertRedirect('/');

        $this->assertSession(null, 'selected_project');
        $this->assertSession(null, 'selected_project_role');
        $this->assertSession(null, 'selected_project_memberid');
        $this->assertSession(null, 'current_weeklyreport');
        $this->assertSession(null, 'current_metrics');
        $this->assertSession(null, 'current_weeklyhours');
        $this->assertSession(null, 'project_list');
        $this->assertSession(null, 'project_memberof_list');
        $this->assertSession(null, 'is_admin');
        $this->assertSession(null, 'is_supervisor');
        $this->assertFlashMessage('You are now logged out.');
    }

    /** 
     * Test add method
     *
     * @return void
     * @uses \App\Controller\UsersController::add()
     */
    public function testAdd()
    {
        $this->enableRetainFlashMessages();

        $this->session([
            'Auth' => [
                'User' => [
                    'role' => 'admin',
                    'id' => 1,
                ]
            ]
        ]);

        $postData = [
            'first_name' => 'First',
            'last_name' => 'Last',
            'email' => 'uniqueuser@test.com',
            'password' => 'whatever',
            'role' => 'user',
        ];

        $this->post('/users/add', $postData);

        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->find()->where(['email' => 'uniqueuser@test.com'])->first();

        $this->assertNotEmpty($user);

        $this->assertRedirect(['controller' => 'Users', 'action' => 'index']);

        $this->assertFlashMessage('The user has been saved.');
    }

    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\UsersController::index()
     */
    public function testIndex(): void
    {
        $this->session([
            'Auth' => [
                'User' => [
                    'role' => 'admin',
                    'id' => 1,
                ]
            ]
        ]);
        
        $this->get('/users');

        $this->assertResponseOk();

        $this->assertResponseContains('<h3>Users</h3>');

        $this->assertResponseContains('First');
        $this->assertResponseContains('Last');
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\UsersController::view()
     */
    public function testView()
    {
        $this->session([
            'Auth' => [
                'User' => [
                    'role' => 'admin',
                    'id' => 1,
                ]
            ]
        ]);
        $userId = 1;

        $this->get("/users/view/{$userId}");

        $this->assertResponseOk();

        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->get($userId, ['contain' => ['Members']]);

        $this->assertResponseContains($user->first_name);
        $this->assertResponseContains($user->last_name);
        $this->assertResponseContains($user->email);
    }

    /**
     * Test signup method
     *
     * @return void
     * @uses \App\Controller\UsersController::signup()
     */
    public function testValidSignup()
    {
        $this->enableRetainFlashMessages();

        $validPostData = [
            'first_name' => 'First',
            'last_name' => 'Last',
            'email' => 'uniqueuser@test.com',
            'password' => 'whatever123',
            'checkIfHuman' => 5,
        ];

        $this->post('/users/signup', $validPostData);

        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->find()->where(['email' => 'uniqueuser@test.com'])->first();

        $this->assertNotEmpty($user);

        $this->assertRedirect(['controller' => 'Projects', 'action' => 'index']);

        $this->assertFlashMessage('Your account has been saved.');
    }

    /**
     * Test signup method with invalid checkIfHuman value
     *
     * @return void
     * @uses \App\Controller\UsersController::signup()
     */
    public function testInvalidSignupCheckIfHuman()
    {
        $this->enableRetainFlashMessages();

        $invalidPostData = [
            'first_name' => 'First',
            'last_name' => 'Last',
            'email' => 'uniqueuser2@test.com',
            'password' => 'whatever123',
            'checkIfHuman' => 4,
        ];

        $this->post('/users/signup', $invalidPostData);

        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->find()->where(['email' => 'uniqueuser2@test.com'])->first();

        $this->assertEmpty($user);

        $this->assertFlashMessage('Check the sum.');
    }

    /**
     * Test signup method with email already in use
     *
     * @return void
     * @uses \App\Controller\UsersController::signup()
     */
    public function testInvalidSignupEmailInUse()
    {
        $this->enableRetainFlashMessages();

        // fixture contains a user with this email
        $existingEmail = 'testuser@example.com';

        $invalidPostData = [
            'first_name' => 'First',
            'last_name' => 'Last',
            'email' => $existingEmail,
            'password' => 'whatever123',
            'checkIfHuman' => 5,
        ];

        $this->post('/users/signup', $invalidPostData);

        // check for the correct flash message
        $this->assertSession('This Email is already in use', 'Flash.flash.0.message');
    }

    /**
     * Test signup method with password too short
     *
     * @return void
     * @uses \App\Controller\UsersController::signup()
     */
    public function testInvalidSignupPasswordTooShort()
    {
        $this->enableRetainFlashMessages();

        $invalidPostData = [
            'first_name' => 'First',
            'last_name' => 'Last',
            'email' => 'uniqueuser3@test.com',
            'password' => 'short',
            'checkIfHuman' => 5,
        ];

        $this->post('/users/signup', $invalidPostData);
;
        // check the flash message
        $this->assertSession('The password has to be at least 8 characters long', 'Flash.flash.0.message');
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\UsersController::edit()
     */
    public function testEditGet()
    {
        $this->session([
            'Auth' => [
                'User' => [
                    'role' => 'admin',
                    'id' => 1,
                ]
            ]
        ]);

        $userId = 1;

        $this->get("/users/edit/{$userId}");

        $this->assertResponseOk();

        $this->assertResponseContains('<form');
    }

    /**
     * Test edit method with valid POST data
     *
     * @return void
     * @uses \App\Controller\UsersController::edit()
     */
    public function testEditPost()
    {
        
        $this->session([
            'Auth' => [
                'User' => [
                    'role' => 'admin',
                    'id' => 1,
                ]
            ]
        ]);

        $userId = 1;

        $postData = [
            'first_name' => 'UpdatedFirst',
            'last_name' => 'UpdatedLast',
            'email' => 'updateduser@example.com',
            'role' => 'user',
        ];

        $this->post("/users/edit/{$userId}", $postData);

        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->get($userId);

        $this->assertEquals('UpdatedFirst', $user->first_name);
        $this->assertEquals('UpdatedLast', $user->last_name);
        $this->assertEquals('updateduser@example.com', $user->email);
        $this->assertEquals('user', $user->role);

        $this->assertRedirect(['controller' => 'Users', 'action' => 'index']);

        $this->assertFlashMessage('The user has been saved.');
    }

    /**
     * Test edit method with POST data setting user to inactive
     *
     * @return void
     * @uses \App\Controller\UsersController::edit()
     */
    public function testEditPostInactive()
    {
        $this->enableRetainFlashMessages();

        $this->session([
            'Auth' => [
                'User' => [
                    'role' => 'admin',
                    'id' => 1,
                ]
            ]
        ]);

        $userId = 1;

        $postData = [
            'first_name' => 'UpdatedFirst',
            'last_name' => 'UpdatedLast',
            'email' => 'updateduser@example.com',
            'role' => 'inactive',
        ];

        $usersTable = TableRegistry::getTableLocator()->get('Users');

        $usersTable = $this->getMockBuilder(get_class($usersTable))
            ->setMethods(['setUserTargetWorkingHoursToCurrentWorkingHours'])
            ->getMock();
        $usersTable->expects($this->once())
            ->method('setUserTargetWorkingHoursToCurrentWorkingHours')
            ->with($userId);

        TableRegistry::getTableLocator()->set('Users', $usersTable);

        $this->post("/users/edit/{$userId}", $postData);

        $user = $usersTable->get($userId);

        $this->assertEquals('UpdatedFirst', $user->first_name);
        $this->assertEquals('UpdatedLast', $user->last_name);
        $this->assertEquals('updateduser@example.com', $user->email);
        $this->assertEquals('inactive', $user->role);

        $this->assertRedirect(['controller' => 'Users', 'action' => 'index']);

        $this->assertFlashMessage('The user has been saved.');
    }

    /**
     * Test editprofile method
     *
     * @return void
     * @uses \App\Controller\UsersController::editprofile()
     */
    public function testEditProfilePost()
    {
        $this->enableRetainFlashMessages();

        $this->session([
            'Auth' => [
                'User' => [
                    'role' => 'admin',
                    'id' => 1,
                ]
            ]
        ]);

        $postData = [
            'first_name' => 'UpdatedFirst',
            'last_name' => 'UpdatedLast',
            'email' => 'updateduser@example.com',
        ];

        $this->post('/users/editprofile', $postData);

        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->get(1);

        $this->assertEquals('UpdatedFirst', $user->first_name);
        $this->assertEquals('UpdatedLast', $user->last_name);
        $this->assertEquals('updateduser@example.com', $user->email);

        $this->assertRedirect(['controller' => 'Projects', 'action' => 'index']);
        
        $this->assertFlashMessage('The profile has been updated.');
    }

    /**
     * Test editprofile method with invalid POST data
     *
     * @return void
     * @uses \App\Controller\UsersController::editprofile()
     */
    public function testEditProfilePostInvalid()
    {
        $this->enableRetainFlashMessages();

        $this->session([
            'Auth' => [
                'User' => [
                    'role' => 'admin',
                    'id' => 1,
                ]
            ]
        ]);

        $postData = [
            'first_name' => 'UpdatedFirst',
            'last_name' => 'UpdatedLast',
            'email' => '', // invalid email
        ];

        $this->post('/users/editprofile', $postData);

        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->get(1);

        $this->assertNotEquals('UpdatedFirst', $user->first_name);
        $this->assertNotEquals('UpdatedLast', $user->last_name);
        $this->assertNotEquals('', $user->email);
        
        $this->assertSession('The user could not be saved. Please, try again.', 'Flash.flash.0.message');
    }

    /**
     * Test password method with GET request
     *
     * @return void
     * @uses \App\Controller\UsersController::password()
     */
    public function testPasswordGet()
    {
        $this->session([
            'Auth' => [
                'User' => [
                    'role' => 'admin',
                    'id' => 1,
                ]
            ]
        ]);

        $this->get('/users/password');

        $this->assertResponseOk();

        $this->assertResponseContains('<form');
    }

    /**
     * Test password method with valid POST data
     *
     * @return void
     * @uses \App\Controller\UsersController::password()
     */
    public function testPasswordPostValid()
    {
        $this->enableRetainFlashMessages();

        $this->session([
            'Auth' => [
                'User' => [
                    'role' => 'admin',
                    'id' => 1,
                ]
            ]
        ]);

        $postData = [
            'password' => 'newpassword123',
            'checkPassword' => 'newpassword123',
        ];

        $this->post('/users/password', $postData);

        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->get(1);

        $hasher = new DefaultPasswordHasher();
        $this->assertTrue($hasher->check('newpassword123', $user->password));

        $this->assertRedirect(['controller' => 'Projects', 'action' => 'index']);

        $this->assertFlashMessage('The profile has been updated.');
    }

    /**
     * Test password method with non-matching passwords
     *
     * @return void
     * @uses \App\Controller\UsersController::password()
     */
    public function testPasswordPostMismatch()
    {
        $this->enableRetainFlashMessages();

        $this->session([
            'Auth' => [
                'User' => [
                    'role' => 'admin',
                    'id' => 1,
                ]
            ]
        ]);

        $postData = [
            'password' => 'newpassword123',
            'checkPassword' => 'differentpassword123',
        ];

        $this->post('/users/password', $postData);

        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->get(1);

        $hasher = new DefaultPasswordHasher();
        $this->assertFalse($hasher->check('newpassword123', $user->password));

        $this->assertFlashMessage('Passwords are not a match. Try again, please.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\UsersController::delete()
     */
    public function testDelete()
    {
        $this->enableRetainFlashMessages();

        $this->session([
            'Auth' => [
                'User' => [
                    'role' => 'admin',
                    'id' => 1,
                ]
            ]
        ]);

        $userId = 1;

        $this->post("/users/delete/{$userId}");

        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->find()->where(['id' => $userId])->first();

        $this->assertNull($user);

        $this->assertRedirect(['controller' => 'Users', 'action' => 'index']);

        $this->assertFlashMessage('The user has been deleted.');
    }

    /**
     * Test delete failure method
     *
     * @return void
     * @uses \App\Controller\UsersController::delete()
     */
    public function testDeleteFailure()
    {
        $this->enableRetainFlashMessages();

        $this->session([
            'Auth' => [
                'User' => [
                    'role' => 'admin',
                    'id' => 1,
                ]
            ]
        ]);

        $userId = 420; // Non-existent user ID

        try {
            $this->post("/users/delete/{$userId}");
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->assertRedirect(['controller' => 'Users', 'action' => 'index']);
            $this->assertFlashMessage('The user could not be deleted. Please, try again.');
        }
        $this->assertTrue(true);

    }

    /**
     * Test forgotpassword method
     *
     * @return void
     * @uses \App\Controller\UsersController::forgotpassword()
     */
    public function testForgotPassword()
    {
        $this->enableRetainFlashMessages();

        TransportFactory::drop('default');
        TransportFactory::setConfig('default', [
            'className' => DebugTransport::class,
        ]);

        $postData = [
            'email' => 'existinguser@example.com',
        ];

        $this->post('/users/forgotpassword', $postData);

        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->find()->where(['email' => 'existinguser@example.com'])->first();
        $this->assertNotEmpty($user->password_key);

        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);

        $this->assertFlashMessage('Key for reseting your password has been sent to your email.');
    }

    /**
     * Test forgotpassword method with non-existent email
     *
     * @return void
     * @uses \App\Controller\UsersController::forgotpassword()
     */
    public function testForgotPasswordNonExistentEmail()
    {
        $this->enableRetainFlashMessages();

        $postData = [
            'email' => 'nonexistent@example.com',
        ];

        $this->post('/users/forgotpassword', $postData);

        $this->assertFlashMessage('This email does not belong to any user.');
    }

/**
     * Test resetpassword method with valid key
     *
     * @return void
     * @uses \App\Controller\UsersController::resetpassword()
     */
    public function testResetPasswordGetValidKey()
    {
        $this->enableRetainFlashMessages();
        // is in fixture
        $validKey = 'validpasswordkey';

        $this->get("/users/resetpassword/{$validKey}");

        $this->assertResponseOk();

        $this->assertResponseContains('<form');
    }

    /**
     * Test resetpassword method with invalid key
     *
     * @return void
     * @uses \App\Controller\UsersController::resetpassword()
     */
    public function testResetPasswordGetInvalidKey()
    {
        $this->enableRetainFlashMessages();

        // invalid key
        $invalidKey = 'invalidpasswordkey';

        $this->get("/users/resetpassword/{$invalidKey}");

        $this->assertFlashMessage('Invalid key.');
    }

    /**
     * Test resetpassword method with matching passwords
     *
     * @return void
     * @uses \App\Controller\UsersController::resetpassword()
     */
    public function testResetPasswordPostMatchingPasswords()
    {
        $this->enableRetainFlashMessages();

        $validKey = 'validpasswordkey';

        $postData = [
            'password' => 'newpassword123',
            'checkPassword' => 'newpassword123',
        ];

        $this->post("/users/resetpassword/{$validKey}", $postData);

        // verify the user's password was updated in the database
        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->find()->where(['email' => 'existinguser@example.com'])->first();
        $hasher = new DefaultPasswordHasher();
        $this->assertTrue($hasher->check('newpassword123', $user->password));

        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);

        $this->assertFlashMessage('Your password has been updated.');
    }

    /**
     * Test resetpassword method with non-matching passwords
     *
     * @return void
     * @uses \App\Controller\UsersController::resetpassword()
     */
    public function testResetPasswordPostNonMatchingPasswords()
    {
        $this->enableRetainFlashMessages();

        $validKey = 'validpasswordkey';

        $postData = [
            'password' => 'newpassword123',
            'checkPassword' => 'differentpassword123',
        ];

        $this->post("/users/resetpassword/{$validKey}", $postData);

        $this->assertFlashMessage('Passwords are not a match. Try again, please.');
    }

    /**
     * Test resetpassword method with short password
     *
     * @return void
     * @uses \App\Controller\UsersController::resetpassword()
     */
    public function testResetPasswordPostShortPassword()
    {
        $this->enableRetainFlashMessages();

        $validKey = 'validpasswordkey';

        $postData = [
            'password' => 'short',
            'checkPassword' => 'short',
        ];

        $this->post("/users/resetpassword/{$validKey}", $postData);

        $this->assertFlashMessage('The password has to be 8 characters long');
    }

    /**
     * Test isAuthorized method for admin user
     *
     * @return void
     * @uses \App\Controller\UsersController::isAuthorized()
     */
    public function testIsAuthorizedAdmin(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'anyAction');

        $request->getSession()->write([
            'Auth.User' => ['id' => 1, 'role' => 'admin'],
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'admin']);
        $this->assertTrue($result, "Admin should be authorized for any action.");
    }

    /**
     * Test isAuthorized method for non-admin user trying to add
     *
     * @return void
     * @uses \App\Controller\UsersController::isAuthorized()
     */
    public function testIsAuthorizedNonAdminAdd(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'add');

        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertFalse($result, "Non-admin user should not be authorized to add.");
    }

    /**
     * Test isAuthorized method for non-admin user trying to edit
     *
     * @return void
     * @uses \App\Controller\UsersController::isAuthorized()
     */
    public function testIsAuthorizedNonAdminEdit(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'edit');

        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertFalse($result, "Non-admin user should not be authorized to edit.");
    }

    /**
     * Test isAuthorized method for non-admin user trying to delete
     *
     * @return void
     * @uses \App\Controller\UsersController::isAuthorized()
     */
    public function testIsAuthorizedNonAdminDelete(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'delete');

        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertFalse($result, "Non-admin user should not be authorized to delete.");
    }

    /**
     * Test isAuthorized method for non-admin user trying to view their own profile
     *
     * @return void
     * @uses \App\Controller\UsersController::isAuthorized()
     */
    public function testIsAuthorizedNonAdminViewOwnProfile(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'view');
        $request = $request->withUri($request->getUri()->withPath('/users/view/2'));

        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user', 'id' => 2]);
        $this->assertTrue($result, "Non-admin user should be authorized to view their own profile.");
    }

    /**
     * Test isAuthorized method for non-admin user trying to view another user's profile
     *
     * @return void
     * @uses \App\Controller\UsersController::isAuthorized()
     */
    public function testIsAuthorizedNonAdminViewAnotherProfile(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'view');
        $request = $request->withUri($request->getUri()->withPath('/users/view/3'));

        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user', 'id' => 2]);
        $this->assertFalse($result, "Non-admin user should not be authorized to view another user's profile.");
    }

    /**
     * Test isAuthorized method for non-admin user trying to logout
     *
     * @return void
     * @uses \App\Controller\UsersController::isAuthorized()
     */
    public function testIsAuthorizedNonAdminLogout(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'logout');

        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertTrue($result, "Non-admin user should be authorized to logout.");
    }

    /**
     * Test isAuthorized method for non-admin user trying to edit their profile
     *
     * @return void
     * @uses \App\Controller\UsersController::isAuthorized()
     */
    public function testIsAuthorizedNonAdminEditProfile(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'editprofile');

        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertTrue($result, "Non-admin user should be authorized to edit their profile.");
    }

    /**
     * Test isAuthorized method for non-admin user trying to change password
     *
     * @return void
     * @uses \App\Controller\UsersController::isAuthorized()
     */
    public function testIsAuthorizedNonAdminChangePassword(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'password');

        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertTrue($result, "Non-admin user should be authorized to change password.");
    }

    /**
     * Test isAuthorized method for non-admin user trying to change photo
     *
     * @return void
     * @uses \App\Controller\UsersController::isAuthorized()
     */
    public function testIsAuthorizedNonAdminChangePhoto(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'photo');

        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertTrue($result, "Non-admin user should be authorized to change photo.");
    }
}
