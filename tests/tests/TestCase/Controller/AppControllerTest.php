<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\AppController;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\AppController Test Case
 */
class AppControllerTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Users',
    ];

    /**
     * Controller instance
     *
     * @var \App\Controller\AppController
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
        $this->controller = new AppController(new ServerRequest());
    }

    /**
     * Test admin access
     *
     * @return void
     */
    public function testAdminAccess(): void
    {
        $user = ['role' => 'admin'];
        $this->assertTrue($this->controller->isAuthorized($user));
    }

    /**
     * Test inactive user access
     *
     * @return void
     */
    public function testInactiveUserAccess(): void
    {
        $user = ['role' => 'inactive'];
        $this->assertFalse($this->controller->isAuthorized($user));
    }

    /**
     * Test project member access to index action
     *
     * @return void
     */
    public function testProjectMemberAccessIndex(): void
    {
        $this->controller->getRequest()->getSession()->write('selected_project_role', 'member');
        $user = ['role' => 'member'];

        // member can access index action
        $this->controller->setRequest($this->controller->getRequest()->withParam('action', 'index'));
        $this->assertTrue($this->controller->isAuthorized($user));
    }

    /**
     * Test project member access to view action
     *
     * @return void
     */
    public function testProjectMemberAccessView(): void
    {
        $this->controller->getRequest()->getSession()->write('selected_project_role', 'member');
        $user = ['role' => 'member'];

        // member can access view action
        $this->controller->setRequest($this->controller->getRequest()->withParam('action', 'view'));
        $this->assertTrue($this->controller->isAuthorized($user));
    }

    /**
     * Test project member access to add action
     *
     * @return void
     */
    public function testProjectMemberAccessAdd(): void
    {
        $this->controller->getRequest()->getSession()->write('selected_project_role', 'member');
        $user = ['role' => 'member'];

        // member cannot access add action
        $this->controller->setRequest($this->controller->getRequest()->withParam('action', 'add'));
        $this->assertFalse($this->controller->isAuthorized($user));
    }

    /**
     * Test non-member access to index action
     *
     * @return void
     */
    public function testNonMemberAccessIndex(): void
    {
        $this->controller->getRequest()->getSession()->write('selected_project_role', 'notmember');
        $user = ['role' => 'member'];

        // non-member cannot access index action
        $this->controller->setRequest($this->controller->getRequest()->withParam('action', 'index'));
        $this->assertFalse($this->controller->isAuthorized($user));
    }

    /**
     * Test supervisor access to add action
     *
     * @return void
     */
    public function testSupervisorAccessAdd(): void
    {
        $this->controller->getRequest()->getSession()->write('selected_project_role', 'supervisor');
        $user = ['role' => 'supervisor'];

        // supervisor can access add action
        $this->controller->setRequest($this->controller->getRequest()->withParam('action', 'add'));
        $this->assertTrue($this->controller->isAuthorized($user));
    }

    /**
     * Test supervisor access to edit action
     *
     * @return void
     */
    public function testSupervisorAccessEdit(): void
    {
        $this->controller->getRequest()->getSession()->write('selected_project_role', 'supervisor');
        $user = ['role' => 'supervisor'];

        // supervisor can access edit action
        $this->controller->setRequest($this->controller->getRequest()->withParam('action', 'edit'));
        $this->assertTrue($this->controller->isAuthorized($user));
    }

    /**
     * Test supervisor access to delete action
     *
     * @return void
     */
    public function testSupervisorAccessDelete(): void
    {
        $this->controller->getRequest()->getSession()->write('selected_project_role', 'supervisor');
        $user = ['role' => 'supervisor'];

        // supervisor can access delete action
        $this->controller->setRequest($this->controller->getRequest()->withParam('action', 'delete'));
        $this->assertTrue($this->controller->isAuthorized($user));
    }

    /**
     * Test supervisor access to addmultiple action
     *
     * @return void
     */
    public function testSupervisorAccessAddMultiple(): void
    {
        $this->controller->getRequest()->getSession()->write('selected_project_role', 'supervisor');
        $user = ['role' => 'supervisor'];

        // supervisor can access addmultiple action
        $this->controller->setRequest($this->controller->getRequest()->withParam('action', 'addmultiple'));
        $this->assertTrue($this->controller->isAuthorized($user));
    }
}