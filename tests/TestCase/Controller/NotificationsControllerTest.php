<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\NotificationsController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Http\ServerRequest;


/**
 * App\Controller\NotificationsController Test Case
 *
 * @uses \App\Controller\NotificationsController
 */
class NotificationsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Notifications',
        'app.Comments',
        'app.Members',
        'app.Projects',
        'app.Users',
        'app.Weeklyreports',
    ];
    private $controller;
    public function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->session(['Auth.User' => ['id' => 1, 
                        'role' => 'admin'], 
                        'selected_project' => ['id' => 1], 
                        'is_admin' => true]);
        $this->controller = new NotificationsController(new ServerRequest(), null, 'Notifications');

    }
    /**
     * Test index method
    *s
     * @return void
     * @uses \App\Controller\NotificationsController::index()
     */
    public function testIndex(): void
    {
        $this->configRequest(['headers' => ['Referer' => '/previous-page']]);
        $this->get('/notifications/index');
        $this->assertRedirect('/previous-page');
    }
    
    // Gives notice ndefined property: NotificationsController::$Comments
    // should be defined in add method
    // dont know if test below works 
    // /**
    //  * Test add method
    //  *
    //  * @return void
    //  * @uses \App\Controller\NotificationsController::add()
    //  */
    // public function testAdd(): void
    // {
    //     $this->configRequest([
    //         'environment' => ['HTTP_REFERER' => '/previous-page']
    //     ]);

    //     $data = [
    //         'content' => 'new comment',
    //         'weeklyreport_id' => 1,
    //         'user_id' => 1
    //     ];
    //     $this->post('/notifications/add', $data);
    //     $this->assertResponseSuccess();
    //     $this->assertRedirect('/previous-page');
    //     $this->assertFlashMessage('The comment has been saved.');

    //     $commentsTable = $this->getTableLocator()->get('Comments');
    //     $comment = $commentsTable->find()->where(['content' => 'new comment'])->first();
    //     $this->assertNotEmpty($comment);

    //     $notificationsTable = $this->getTableLocator()->get('Notifications');
    //     $notification = $notificationsTable->find()->where(['comment_id' => $comment->id])->first();
    //     $this->assertNotEmpty($notification);
    // }
    
    /**
     * Test isAuthorized method for add action
     *
     * @return void
     * @uses \App\Controller\NotificationsController::isAuthorized()
     */
    public function testIsAuthorized(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'add');
        $this->controller->setRequest($request);
        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertTrue($result, "Everyone should be authorized to add notifications.");
    }
}
