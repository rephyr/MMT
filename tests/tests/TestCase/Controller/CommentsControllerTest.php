<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\CommentsController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Http\ServerRequest;
use Cake\Http\Response;
use Cake\Http\Session;


/**
 * App\Controller\CommentsController Test Case
 *
 * @uses \App\Controller\CommentsController
 */
class CommentsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Comments',
        'app.Users',
        'app.Weeklyreports',
        'app.Notifications',
        'app.Members',
    ];

    /**
     * @var CommentsController
     */
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
        $request = new ServerRequest();
        $response = new Response();
        $this->controller = new CommentsController($request, $response);
    }

    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\CommentsController::index()
     */
    public function testIndex(): void
    {
        // index should redirect to previous page
        $this->configRequest([
            'headers' => ['Referer' => '/previous-page']
        ]);

        $this->get('/comments/index');
        $this->assertResponseCode(302);
        $this->assertRedirect('/previous-page');
    }
    /**
     * Test add method
     *
     * @return void
     * @uses \App\Controller\CommentsController::add()
     */
    public function testAdd(): void
    {
        // new comment
        $data = [
            'content' => 'This is a test comment.',
            'user_id' => 1,
            'weeklyreport_id' => 1,
        ];

        $this->post('/comments/add', $data);

        $this->assertResponseSuccess();
        $this->assertFlashMessage('The comment has been saved.');
        // check the comment
        $commentsTable = $this->getTableLocator()->get('Comments');
        $comment = $commentsTable->find()->where(['content' => 'This is a test comment.'])->first();

        $this->assertNotEmpty($comment);
        // should have a notification
        $notificationsTable = $this->getTableLocator()->get('Notifications');
        $notification = $notificationsTable->find()->where(['comment_id' => $comment->id])->first();
        $this->assertNotEmpty($notification);
    }

    /**
     * Test edit method with success
     *
     * @return void
     * @uses \App\Controller\CommentsController::edit()
     */
    public function testEditSuccess(): void
    {
        $data = [
            'content' => 'Edited comment.',
        ];
    
        $this->post('/comments/edit/1', $data);
    
        $this->assertResponseSuccess();
        $this->assertFlashMessage('The comment has been saved.');
    
        // should be updated
        $commentsTable = $this->getTableLocator()->get('Comments');
        $comment = $commentsTable->get(1);
        $this->assertEquals('Edited comment.', $comment->content);
        $this->assertNotEmpty($comment->date_modified);
    }
    /**
     * Test edit method with validation error
     *
     * @return void
     * @uses \App\Controller\CommentsController::edit()
     */
    public function testEditValidationError(): void
    {
        $data = [
            'content' => '',
        ];

        $this->post('/comments/edit/1', $data);

        $this->assertResponseSuccess();
        $this->assertFlashMessage('The comment can not be empty.');
    }
    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\CommentsController::delete()
     */
    public function testDelete(): void
    {
        $this->configRequest([
            'headers' => ['Referer' => '/previous-page']
        ]);

        // ensure comments exist
        $commentsTable = $this->getTableLocator()->get('Comments');
        $comment = $commentsTable->get(1);
        $this->assertNotEmpty($comment);
        $this->post('/comments/delete/1');

        $this->assertResponseCode(302);
        $this->assertFlashMessage('The comment has been deleted.');
        $this->assertRedirect('/previous-page');

        // should be deleted
        $comment = $commentsTable->find()->where(['id' => 1])->first();
        $this->assertEmpty($comment);
    }

    /**
     * Test that everyone can add comments
     *
     * @return void
     */
    public function testIsAuthorizedAdd(): void
    {
        // everyone can add comments
        $request = new ServerRequest();
        $request = $request->withParam('action', 'add');
        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertTrue($result, "Everyone should be authorized to add comments.");
    }

    /**
     * Test that only the owner can edit comments
     *
     * @return void
     */
    public function testIsAuthorizedEditOwner(): void
    {
        // only the owner can edit comments
        $request = new ServerRequest();
        $request = $request->withParam('action', 'edit')
                           ->withParam('pass', [2]);

        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertTrue($result, "Owner should be authorized to edit comments.");
    }

    /**
     * Test that non-owner cannot edit comments
     *
     * @return void
     */
    public function testIsAuthorizedEditNonOwner(): void
    {
        // non-owner cannot edit comments
        $request = new ServerRequest();
        $request = $request->withParam('action', 'edit')
                           ->withParam('pass', [2]);

        $request->getSession()->write([
            'Auth.User' => ['id' => 1, 'role' => 'user'],
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertFalse($result, "Non-owner should not be authorized to edit comments.");
    }

    /**
     * Test that only the owner can delete comments
     *
     * @return void
     */
    public function testIsAuthorizedDeleteOwner(): void
    {
        // only the owner can delete comments
        $request = new ServerRequest();
        $request = $request->withParam('action', 'delete')
                           ->withParam('pass', [2]);

        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertTrue($result, "Owner should be authorized to delete comments.");
    }

    /**
     * Test that non-owner cannot delete comments
     *
     * @return void
     */
    public function testIsAuthorizedDeleteNonOwner(): void
    {
        // non-owner cannot delete comments
        $request = new ServerRequest();
        $request = $request->withParam('action', 'delete')
                           ->withParam('pass', [2]);

        $request->getSession()->write([
            'Auth.User' => ['id' => 1, 'role' => 'user'],
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertFalse($result, "Non-owner should not be authorized to delete comments.");
    }
}
