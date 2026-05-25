<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\NotesController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Http\ServerRequest;

/**
 * App\Controller\NotesController Test Case
 *
 * @uses \App\Controller\NotesController
 */
class NotesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Notes',
    ];
    /**
     * @var \App\Controller\NotesController
     */
    private $controller;
    /**
     * @var \App\Model\Table\NotesTable
     */
    private $Notes;

    public function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();
        $this->session(['Auth.User' => ['id' => 1, 
                        'role' => 'admin'], 
                        'is_admin' => true]);
        $this->Notes = $this->getTableLocator()->get('Notes');
        $this->controller = new NotesController(new ServerRequest(), null, 'Notes');
    }
    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\NotesController::index()
     */
    public function testIndex(): void
    {
        $this->get('/notes');
        $this->assertResponseOk();
        $this->assertResponseContains('Notes'); 
        
        $notes = $this->viewVariable('notes');
        $this->assertNotEmpty($notes);
        
        $expectedOrder = [
            ['note_read' => false, 'created_on' => '2015-01-02'],
            ['note_read' => false, 'created_on' => '2015-01-01'],
        ];
        $actualOrder = array_map(function ($note) {
            return [
                'note_read' => (bool)$note->note_read,
                'created_on' => $note->created_on->format('Y-m-d'),
            ];
        }, $notes->toArray());
        $this->assertEquals($expectedOrder, $actualOrder);
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\NotesController::view()
     */
    public function testView(): void
    {
        $data = [
            'id' => 1,
            'note_read' => false,
            'created_on' => '2015-01-01',
            'content' => 'Test note content'
        ];
        $notesTable = $this->getTableLocator()->get('Notes');
        $note = $notesTable->newEntity($data);
        $notesTable->save($note);

        $this->get('/notes/view/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Test note content');

        // Check if the note is marked as read
        $note = $notesTable->get(1);
        $this->assertTrue((bool)$note->note_read);
        // note
        $viewNote = $this->viewVariable('note');
        $this->assertEquals($note->id, $viewNote->id);
        $this->assertEquals($note->content, $viewNote->content);
    }

    /**
     * Test add method success
     *
     * @return void
     * @uses \App\Controller\NotesController::add()
     */
    public function testAddSuccess(): void
    {
        $this->session([
            'Auth.User' => [
                'id' => 1,
                'email' => 'testuser@example.com',
                'role' => 'user'
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'developer',
        ]);

        // valid add request
        $data = [
            'content' => 'Test note content',
            'created_on' => '2015-10-01 00:00:00',
        ];
        $this->post('/notes/add', $data);
        $this->assertResponseSuccess();
        $this->assertFlashMessage('The feedback has been saved.');

        $notesTable = $this->getTableLocator()->get('Notes');
        $note = $notesTable->find()->where(['content' => 'Test note content'])->first();
        $this->assertNotEmpty($note);
        $this->assertEquals('developer', $note->project_role);
        $this->assertEquals('testuser@example.com', $note->email);
    }

    /**
     * Test add method validation error
     *
     * @return void
     * @uses \App\Controller\NotesController::add()
     */
    public function testAddValidationError(): void
    {
        $this->session([
            'Auth.User' => [
                'id' => 1,
                'email' => 'testuser@example.com',
                'role' => 'user'
            ],
            'selected_project' => ['id' => 1],
            'selected_project_role' => 'developer',
        ]);

        // invalid add request
        $data = [
            'content' => '', // empty content should fail
            'created_on' => '2015-10-01 00:00:00',
        ];
        $this->post('/notes/add', $data);
        $this->assertResponseSuccess();
        $this->assertFlashMessage('The feedback could not be saved. Please, try again.');

        $notesTable = $this->getTableLocator()->get('Notes');
        $note = $notesTable->find()->where(['content' => ''])->first();
        $this->assertEmpty($note);
    }

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\NotesController::delete()
     */
    public function testDelete(): void
    {
        $this->post('/notes/delete/1');
        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'index']);
        $this->assertFlashMessage('The feedback has been deleted.');
    
        $notesTable = $this->getTableLocator()->get('Notes');
        $note = $notesTable->find()->where(['id' => 1])->first();
        $this->assertEmpty($note);
    }

    /**
     * Test isAuthorized method for admin
     *
     * @return void
     * @uses \App\Controller\NotesController::isAuthorized()
     */
    public function testIsAuthorizedAdmin(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'anyAction');
        $request->getSession()->write([
            'Auth.User' => ['id' => 1, 'role' => 'admin'],
            'selected_project_role' => 'admin',
        ]);
        $this->controller->setRequest($request);
        $result = $this->controller->isAuthorized(['role' => 'admin']);
        $this->assertTrue($result, "Admin should be authorized for any action.");
    }

    /**
     * Test isAuthorized method for senior developer adding note
     *
     * @return void
     * @uses \App\Controller\NotesController::isAuthorized()
     */
    public function testIsAuthorizedSeniorDeveloperAddNote(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'add');
        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
            'selected_project_role' => 'senior_developer',
        ]);
        $this->controller->setRequest($request);
        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertTrue($result, "Senior developer should be authorized to add note.");
    }

    /**
     * Test isAuthorized method for supervisor adding note
     *
     * @return void
     * @uses \App\Controller\NotesController::isAuthorized()
     */
    public function testIsAuthorizedSupervisorAddNote(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'add');
        $request->getSession()->write([
            'Auth.User' => ['id' => 3, 'role' => 'user'],
            'selected_project_role' => 'supervisor',
        ]);
        $this->controller->setRequest($request);
        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertTrue($result, "Supervisor should be authorized to add note.");
    }

    /**
     * Test isAuthorized method for developer adding note
     *
     * @return void
     * @uses \App\Controller\NotesController::isAuthorized()
     */
    public function testIsAuthorizedDeveloperAddNote(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'add');
        $request->getSession()->write([
            'Auth.User' => ['id' => 4, 'role' => 'user'],
            'selected_project_role' => 'developer',
        ]);
        $this->controller->setRequest($request);
        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertTrue($result, "Developer should be authorized to add note.");
    }

    /**
     * Test isAuthorized method for client adding note
     *
     * @return void
     * @uses \App\Controller\NotesController::isAuthorized()
     */
    public function testIsAuthorizedClientAddNote(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'add');
        $request->getSession()->write([
            'Auth.User' => ['id' => 5, 'role' => 'user'],
            'selected_project_role' => 'client',
        ]);
        $this->controller->setRequest($request);
        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertTrue($result, "Client should be authorized to add note.");
    }

    /**
     * Test isAuthorized method for non-authorized role adding note
     *
     * @return void
     * @uses \App\Controller\NotesController::isAuthorized()
     */
    public function testIsAuthorizedNonAuthorizedRoleAddNote(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'add');
        $request->getSession()->write([
            'Auth.User' => ['id' => 6, 'role' => 'user'],
            'selected_project_role' => 'guest',
        ]);
        $this->controller->setRequest($request);
        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertFalse($result, "Guest should not be authorized to add note.");
    }

    /**
     * Test isAuthorized method for non-add action
     *
     * @return void
     * @uses \App\Controller\NotesController::isAuthorized()
     */
    public function testIsAuthorizedNonAddAction(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'edit');
        $request->getSession()->write([
            'Auth.User' => ['id' => 7, 'role' => 'user'],
            'selected_project_role' => 'developer',
        ]);
        $this->controller->setRequest($request);
        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertFalse($result, "Developer should not be authorized to edit note.");
    }
}
