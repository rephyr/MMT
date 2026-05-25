<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\RisksController;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\ORM\TableRegistry;
use Cake\Http\Session;
/**
 * App\Controller\RisksController Test Case
 *
<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\RisksController;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\ORM\TableRegistry;

/**
 * App\Controller\RisksController Test Case
 *
 * @uses \App\Controller\RisksController
 */
class RisksControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Risks',
        'app.Projects',
        'app.Weeklyrisks',
        'app.Users',
    ];

    /**
     * Risks Table
     *
     * @var \App\Model\Table\RisksTable
     */
    protected $Risks;

    /**
     * Weeklyrisks Table
     *
     * @var \App\Model\Table\WeeklyrisksTable
     */
    protected $Weeklyrisks;

    /**
     * Controller instance
     *
     * @var \App\Controller\RisksController
     */
    protected $controller;

    public function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->Risks = $this->getTableLocator()->get('Risks');
        $this->Weeklyrisks = $this->getTableLocator()->get('Weeklyrisks');
        $this->controller = new RisksController(new ServerRequest(), null, 'Risks');
    }

    public function tearDown(): void
    {
        $this->Risks->getConnection()->rollback();
        $this->Weeklyrisks->getConnection()->rollback();

        unset($this->Risks);
        unset($this->Weeklyrisks);
        unset($this->controller);
        parent::tearDown();
    }

    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\RisksController::index()
     */
    public function testIndex()
    {
        $this->session(['Auth.User' => ['id' => 1, 
                        'role' => 'admin'], 
                        'selected_project' => ['id' => 1], 
                        'is_admin' => true]);

        $this->get('/risks');

        $this->assertResponseOk();

        // view should contain these elements
        $this->assertResponseContains('<h3>Project Risks</h3>'); 
        $this->assertResponseContains('<table id="risks-table"'); 

        $risksTable = TableRegistry::getTableLocator()->get('Risks');
        $risks = $risksTable->find()->where(['project_id' => 1])->toArray();

        // check that the risks are passed to the view
        $this->assertNotEmpty($risks);
        foreach ($risks as $risk) {
            $this->assertResponseContains($risk->description);
        }

        // check that the view variables are set
        $this->assertNotEmpty($this->viewVariable('types'));
        $this->assertNotEmpty($this->viewVariable('categories'));
        $this->assertNotEmpty($this->viewVariable('impactTypes'));
        $this->assertNotEmpty($this->viewVariable('statusTypes'));
        $this->assertNotEmpty($this->viewVariable('deletable'));
    }

    /**
     * Test index method with no risks
     *
     * @return void
     * @uses \App\Controller\RisksController::index()
     */
    public function testIndexNoRisks()
    {
        $this->session(['Auth.User' => ['id' => 1, 
                        'role' => 'admin'], 
                        'selected_project' => ['id' => 2], 
                        'is_admin' => true]);
                        
        $this->get('/risks');

        $this->assertResponseOk();

        // view should contain these elements
        $this->assertResponseContains('<h3>Project Risks</h3>'); 
        $this->assertResponseContains('<table id="risks-table"'); 

        // get the risks from the database
        $risksTable = TableRegistry::getTableLocator()->get('Risks');
        $risks = $risksTable->find()->where(['project_id' => 2])->toArray();

        // there should be no risks
        $this->assertEmpty($risks);
    }

    /**
     * Test index method with non-admin user
     *
     * @return void
     * @uses \App\Controller\RisksController::index()
     */
    public function testIndexNonAdmin()
    {
        $this->session(['Auth.User' => ['id' => 2, 
                        'role' => 'user'], 
                        'selected_project' => ['id' => 1], 
                        'is_admin' => false]);

        $this->get('/risks');
        
        $this->assertResponseOk();

        // view should contain these elements
        $this->assertResponseContains('<h3>Project Risks</h3>'); 
        $this->assertResponseContains('<table id="risks-table"'); 

        // get the risks from the database
        $risksTable = TableRegistry::getTableLocator()->get('Risks');
        $risks = $risksTable->find()->where(['project_id' => 1])->toArray();

        // Check that the risks are passed to the view
        $this->assertNotEmpty($risks);
        foreach ($risks as $risk) {
            $this->assertResponseContains($risk->description);
        }
    }
    
    /**
     * Test add method
     *
     * @return void
     * @uses \App\Controller\RisksController::add()
     */
    public function testAddPost()
    {
        $this->session(['Auth.User' => ['id' => 1, 
                        'role' => 'admin'], 
                        'selected_project' => ['id' => 1], 
                        'is_admin' => true]);

        $data = [
            'description' => 'cool desc',
            'impact' => 1,
            'category' => 2,
            'probability' => 4,
            'status' => 0 
        ];

        
        $this->post('/risks/add', $data);

        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'index']);

        $risksTable = TableRegistry::getTableLocator()->get('Risks');
        $risk = $risksTable->find()->where(['description' => 'cool desc'])->first();

        // check that the risk was added
        $this->assertNotEmpty($risk);

        $this->assertEquals(1, $risk->project_id);
        $this->assertEquals('cool desc', $risk->description);
        $this->assertEquals(1, $risk->impact);
        $this->assertEquals(2, $risk->category);
        $this->assertEquals(4, $risk->probability);
        $this->assertEquals(0, $risk->status);
    }

    /**
     * Test add method (POST request failure)
     *
     * @return void
     * @uses \App\Controller\RisksController::add()
     */
    public function testAddFailure()
    {
        // for flash message
        $this->enableRetainFlashMessages();

        $this->session(['Auth.User' => ['id' => 1, 
                        'role' => 'admin'], 
                        'selected_project' => ['id' => 1], 
                        'is_admin' => true]);

        // data with empty description should not be saved
        $data = [
            'description' => '', 
            'impact' => 1,
            'category' => 2,
            'probability' => 4,
            'status' => 0 
        ];

        $this->post('/risks/add', $data);

        $this->assertResponseOk();

        $this->assertFlashMessage('The risk could not be added. Please, try again.');

        $risksTable = TableRegistry::getTableLocator()->get('Risks');
        $risk = $risksTable->find()->where(['description' => ''])->first();

        $this->assertEmpty($risk);
    }

    /**
     * Test delete method (successful deletion)
     *
     * @return void
     * @uses \App\Controller\RisksController::delete()
     */
    public function testDelete()
    {
        $this->session(['Auth.User' => ['id' => 1, 
                        'role' => 'admin'], 
                        'selected_project' => ['id' => 1], 
                        'is_admin' => true]);

        // make a risk to be deleted
        $risksTable = TableRegistry::getTableLocator()->get('Risks');
        $risk = $risksTable->newEntity([
            'project_id' => 1,
            'description' => 'Risk to be deleted',
            'impact' => 1,
            'category' => 2,
            'probability' => 4,
            'status' => 0
        ]);

        $risksTable->save($risk);

        $this->post('/risks/delete/' . $risk->id);

        // check for redirect
        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'index']);

        // should have this flash message
        $this->assertFlashMessage('The risk has been deleted.');

        $deletedRisk = $risksTable->find()->where(['id' => $risk->id])->first();

        $this->assertEmpty($deletedRisk);
    }

    /**
     * Test delete method (failure due to weekly report)
     *
     * @return void
     * @uses \App\Controller\RisksController::delete()
     */
    public function testDeleteFailure()
    {
        $this->session(['Auth.User' => ['id' => 1, 
                        'role' => 'admin'], 
                        'selected_project' => ['id' => 1], 
                        'is_admin' => true]);

        $risksTable = TableRegistry::getTableLocator()->get('Risks');
        $risk = $risksTable->newEntity([
            'project_id' => 1,
            'description' => 'hip hip hooray',
            'impact' => 1,
            'category' => 1,
            'probability' => 1,
            'status' => 0
        ]);
        $risksTable->save($risk);

        // create a weekly risk and link it to the risk
        $weeklyRisksTable = TableRegistry::getTableLocator()->get('Weeklyrisks');
        $weeklyRisk = $weeklyRisksTable->newEntity([
            'risk_id' => $risk->id,
            'weeklyreport_id' => 1,
            'category' => 1,
            'probability' => 1,
            'severity' => 1,
            'impact' => 1,
            'status' => 0
        ]);
        $weeklyRisksTable->save($weeklyRisk);

        $this->delete('/risks/delete/' . $risk->id);

        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'index']);

        $this->assertFlashMessage('This risk is already contained in a weekly report, and thus can not be deleted.');

        // check that the risk was not deleted
        $existingRisk = $risksTable->find()->where(['id' => $risk->id])->first();

        $this->assertNotEmpty($existingRisk);
    }

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\RisksController::edit()
     */
    public function testEdit()
    {
        $this->session(['Auth.User' => ['id' => 1, 
                        'role' => 'admin'], 
                        'selected_project' => ['id' => 1], 
                        'is_admin' => true]);

        $risksTable = TableRegistry::getTableLocator()->get('Risks');
        $risk = $risksTable->newEntity([
            'project_id' => 1,
            'description' => 'Risk to be edited',
            'impact' => 1,
            'category' => 2,
            'probability' => 4,
            'status' => 0
        ]);
        $risksTable->save($risk);

        $data = [
            'description' => 'Updated risk description',
            'impact' => 2,
            'category' => 3,
            'probability' => 5,
            'status' => 1 // 'Mitigated' status
        ];

        $this->post('/risks/edit/' . $risk->id, $data);

        $this->assertResponseSuccess();
        $this->assertRedirect(['action' => 'index']);

        $this->assertFlashMessage('The risk has been saved.');

        $updatedRisk = $risksTable->find()->where(['id' => $risk->id])->first();

        $this->assertNotEmpty($updatedRisk);
        $this->assertEquals('Updated risk description', $updatedRisk->description);
        $this->assertEquals(2, $updatedRisk->impact);
        $this->assertEquals(3, $updatedRisk->category);
        $this->assertEquals(5, $updatedRisk->probability);
        $this->assertEquals(1, $updatedRisk->status); // 'Mitigated' status 
    }

    /**
     * Test edit method (POST request failure)
     *
     * @return void
     * @uses \App\Controller\RisksController::edit()
     */
    public function testEditFailure()
    {
        // for flash message
        $this->enableRetainFlashMessages();

        $this->session(['Auth.User' => ['id' => 1, 
                        'role' => 'admin'], 
                        'selected_project' => ['id' => 1], 
                        'is_admin' => true]);

        $risksTable = TableRegistry::getTableLocator()->get('Risks');
        $risk = $risksTable->newEntity([
            'project_id' => 1,
            'description' => 'Risk to be edited',
            'impact' => 1,
            'category' => 2,
            'probability' => 4,
            'status' => 0
        ]);
        $risksTable->save($risk);
        // make a risk to be edited with empty description
        $data = [
            'description' => '',
            'impact' => 2,
            'category' => 3,
            'probability' => 5,
            'status' => 1 // 'Mitigated' status
        ];

        $this->post('/risks/edit/' . $risk->id, $data);

        $this->assertResponseOk();

        $this->assertFlashMessage('The risk could not be saved. Please, try again.');

        $existingRisk = $risksTable->find()->where(['id' => $risk->id])->first();

        $this->assertNotEmpty($existingRisk);
        // risk should remain unchanged
        $this->assertEquals('Risk to be edited', $existingRisk->description);
        $this->assertEquals(1, $existingRisk->impact);
        $this->assertEquals(2, $existingRisk->category);
        $this->assertEquals(4, $existingRisk->probability);
        $this->assertEquals(0, $existingRisk->status); 
    }

    /**
     * Test addweekly method
     *
     * @return void
     * @uses \App\Controller\RisksController::addweekly()
     */
    public function testAddWeekly()
    {
        $this->session(['Auth.User' => ['id' => 1, 
                        'role' => 'admin'], 
                        'selected_project' => ['id' => 1], 
                        'is_admin' => true]);   

        $risksTable = TableRegistry::getTableLocator()->get('Risks');
        $risk = $risksTable->newEntity([
            'project_id' => 1,
            'description' => 'Risk for weekly report',
            'impact' => 1,
            'category' => 2,
            'probability' => 4,
            'status' => 0,
            'realizations' => 0
        ]);
        $risksTable->save($risk);
    
        $data = [
            'category-' . $risk->id => 3,
            'prob-' . $risk->id => 5,
            'severity-' . $risk->id => 4,
            'impact-' . $risk->id => 2,
            'status-' . $risk->id => 1,
            'real-' . $risk->id => 1
        ];
    
        $this->post('/risks/addweekly', $data);
    
        // check for redirect
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Weeklyhours', 'action' => 'addmultiple']);

        $this->assertFlashMessage('');
    
        $updatedRisk = $risksTable->find()->where(['id' => $risk->id])->first();
    
        $this->assertNotEmpty($updatedRisk);
        $this->assertEquals(1, $updatedRisk->realizations); 
    
        // and check that the risk is in the session
        $currentRisks = $_SESSION['current_risks'];
        $this->assertNotEmpty($currentRisks);
        $this->assertArrayHasKey($risk->id, $currentRisks);
        $this->assertEquals(3, $currentRisks[$risk->id]['category']);
        $this->assertEquals(5, $currentRisks[$risk->id]['probability']);
        $this->assertEquals(4, $currentRisks[$risk->id]['severity']);
        $this->assertEquals(2, $currentRisks[$risk->id]['impact']);
        $this->assertEquals(1, $currentRisks[$risk->id]['status']); 
    }

    /**
     * Test addweekly method failure
     *
     * @return void
     * @uses \App\Controller\RisksController::addweekly()
     */
    public function testAddWeeklyPostFailure()
    {
        $this->enableRetainFlashMessages();
    
        $this->session(['Auth.User' => ['id' => 1, 
                        'role' => 'admin'], 
                        'selected_project' => ['id' => 1], 
                        'is_admin' => true]);  

        $risksTable = $this->getTableLocator()->get('Risks');
        $risk = $risksTable->newEntity([
            'project_id' => 1,
            'description' => 'Risk for weekly report',
            'impact' => 1,
            'category' => 2,
            'probability' => 4,
            'status' => 0,
            'realizations' => 0
        ]);
        $risksTable->save($risk);
    
        // force the save method to return false
        // couldn't figure out another way to force a save failure
        $risksTableMock = $this->getMockBuilder('App\Model\Table\RisksTable')
            ->setMethods(['save'])
            ->getMock();
        $risksTableMock->expects($this->any())
            ->method('save')
            ->will($this->returnValue(false));
    
        $this->getTableLocator()->set('Risks', $risksTableMock);
    
        $data = [
            'category-' . $risk->id => 3,
            'prob-' . $risk->id => 5,
            'severity-' . $risk->id => 4,
            'impact-' . $risk->id => 2,
            'status-' . $risk->id => 1,
            'real-' . $risk->id => 1 
        ];
    
        $this->post('/risks/addweekly', $data);
    
        $this->assertResponseCode(302);
        $this->assertRedirect(['controller' => 'Weeklyhours', 'action' => 'addmultiple']);
    
        $this->assertFlashMessage('Unable to update risk realization count.');
    
        $existingRisk = $risksTable->find()->where(['id' => $risk->id])->first();
    
        $this->assertNotEmpty($existingRisk);
        $this->assertEquals(0, $existingRisk->realizations); 
        // session should not contain current_risks key since the save failed
        $this->assertArrayNotHasKey('current_risks', $_SESSION);
    }
    /**
     * Test getCategories method
     *
     * @return void
     * @uses \App\Controller\RisksController::getCategories()
     */
    public function testGetCategories()
    {
        // Define the expected array result
        $expected = [
            0 => 'Uncategorized',
            1 => 'Political',
            2 => 'Economic',
            3 => 'Social',
            4 => 'Technological',
            5 => 'Environmental',
            6 => 'Legal',
        ];
    
        // Assert that the returned value is the same as the expected array
        $this->assertSame($expected, $this->controller->getCategories());
    }

    /**
     * Test getSeverityProbTypes method
     *
     * @return void
     * @uses \App\Controller\RisksController::getSeverityProbTypes()
     */
    public function testGetSeverityProbTypes()
    {
        $expected = [
            0 => 'None',
            1 => 'Very Low',
            2 => 'Low',
            3 => 'Medium',
            4 => 'High',
            5 => 'Very High',
        ];

        $this->assertSame($expected, $this->controller->getSeverityProbTypes());
    }
    /**
     * Test getImpactTypes method
     *
     * @return void
     * @uses \App\Controller\RisksController::getImpactTypes()
     */
    public function testGetImpactTypes()
    {
        $expected = [
            0 => 'Budget',
            1 => 'Time',
            2 => 'Scope',
            3 => 'Benefit',
        ];

        $this->assertSame($expected, $this->controller->getImpactTypes());
    }

    /**
     * Test getStatusTypes method
     *
     * @return void
     * @uses \App\Controller\RisksController::getStatusTypes()
     */
    public function testGetStatusTypes()
    {
        $expected = [
            0 => 'Active',
            1 => 'Mitigated',
            2 => 'Closed',
        ];

        $this->assertSame($expected, $this->controller->getStatusTypes());
    }

    /**
     * Test checkDeletable method
     *
     * @return void
     * @uses \App\Controller\RisksController::checkDeletable()
     */
    public function testCheckDeletableWhenRiskIsDeletable()
    {
        // mock the Weeklyrisks table to return an empty result set for the given riskId
        $riskId = 1;
        $weeklyRisksTableMock = $this->getMockForModel('Weeklyrisks', ['find']);
        
        $queryMock = $this->getMockBuilder('Cake\ORM\Query')
            ->disableOriginalConstructor()
            ->getMock();
        
        $queryMock->expects($this->once())
            ->method('where')
            ->with(['risk_id' => $riskId])
            ->willReturnSelf();
        
        $queryMock->expects($this->once())
            ->method('toArray')
            ->willReturn([]);  
    
        $weeklyRisksTableMock->expects($this->once())
            ->method('find')
            ->willReturn($queryMock);
    
        TableRegistry::getTableLocator()->set('Weeklyrisks', $weeklyRisksTableMock);
    
        $result = $this->controller->checkDeletable($riskId);
    
        // the risk should be deletable since there are no associated weekly reports
        $this->assertTrue($result, "Risk should be deletable if there are no associated weekly reports.");
    }
    /**
     * Test checkDeletable method when risk is not deletable
     *
     * @return void
     * @uses \App\Controller\RisksController::checkDeletable()
     */
    public function testCheckDeletableWhenRiskIsNotDeletable()
    {
        // new risk
        $risk = $this->Risks->newEntity([
            'project_id' => 1,
            'description' => 'Test Risk',
            'impact' => 1,
            'category' => 2,
            'probability' => 4,
            'status' => 0,
            'realizations' => 0
        ]);
        $this->Risks->save($risk);

        // link risk to a weekly risk
        $weeklyRisk = $this->Weeklyrisks->newEntity([
            'risk_id' => $risk->id,
            'weeklyreport_id' => 1,
            'probability' => 3,
            'impact' => 2,
            'date' => '2023-10-01'
        ]);
        $this->Weeklyrisks->save($weeklyRisk);

        // should return false since the risk is associated with a weekly risk
        $result = $this->controller->checkDeletable($risk->id);
        $this->assertFalse($result, 'Risk should not be deletable when associated with Weeklyrisks.');
    }
    /**
     * Test getLatestRisks method
     *
     * @return void
     * @uses \App\Controller\RisksController::getLatestRisks()
     */
    public function testGetLatestRisks()
    {
        // Clear the Risks and Weeklyrisks tables
        $this->Risks->deleteAll([]);
        $this->Weeklyrisks->deleteAll([]);
    
        // Create a project
        $projectId = 1;
    
        // Create a risk associated with the project
        $risk = $this->Risks->newEntity([
            'project_id' => $projectId,
            'description' => 'Test Risk',
            'impact' => 1,
            'category' => 2,
            'probability' => 4,
            'status' => 0,
            'severity' => 3
        ]);
        $this->Risks->save($risk);
    
        // Create a Weeklyrisk associated with the risk
        $weeklyRisk = $this->Weeklyrisks->newEntity([
            'risk_id' => $risk->id,
            'weeklyreport_id' => 1,
            'probability' => 3,
            'impact' => 2,
            'category' => 2,
            'severity' => 4,
            'status' => 1,
            'date' => '2023-10-01'
        ]);
        $this->Weeklyrisks->save($weeklyRisk);
    
        // Call getLatestRisks method
        $result = $this->controller->getLatestRisks($projectId);
    
        // Expected result
        $expected = [
            $risk->id => [
                'category' => 2,
                'probability' => 3,
                'severity' => 4,
                'impact' => 2,
                'status' => 1
            ]
        ];
    
        // Assert the result
        $this->assertEquals($expected, $result, 'The latest risks should match the expected values.');
    }

    /**
     * Test isAuthorized method for admin
     *
     * @return void
     * @uses \App\Controller\RisksController::isAuthorized()
     */
    public function testIsAuthorizedAdmin(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'anyAction');

        $request->getSession()->write([
            'Auth.User' => ['id' => 1, 'role' => 'admin'],
            'selected_project' => ['id' => 1],
            'is_admin' => true,
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'admin']);
        $this->assertTrue($result, "Admin should be authorized for any action.");
    }

    /**
     * Test isAuthorized method for senior developer adding weekly
     *
     * @return void
     * @uses \App\Controller\RisksController::isAuthorized()
     */
    public function testIsAuthorizedSeniorDeveloperAddWeekly(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'addweekly');

        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
            'selected_project_role' => 'senior_developer',
            'selected_project' => ['id' => 1],
            'is_admin' => false,
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertTrue($result, "Senior developer should be authorized to add weekly.");
    }

    /**
     * Test isAuthorized method for non-senior developer adding weekly
     *
     * @return void
     * @uses \App\Controller\RisksController::isAuthorized()
     */
    public function testIsAuthorizedNonSeniorDeveloperAddWeekly(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'addweekly');

        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
            'selected_project_role' => 'developer',
            'selected_project' => ['id' => 1],
            'is_admin' => false,
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertFalse($result, "Non-senior developer should not be authorized to add weekly.");
    }

    /**
     * Test isAuthorized method for senior developer adding risk
     *
     * @return void
     * @uses \App\Controller\RisksController::isAuthorized()
     */
    public function testIsAuthorizedSeniorDeveloperAddRisk(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'add');

        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
            'selected_project_role' => 'senior_developer',
            'selected_project' => ['id' => 1],
            'is_admin' => false,
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertTrue($result, "Senior developer should be authorized to add risk.");
    }

    /**
     * Test isAuthorized method for non-senior developer adding risk
     *
     * @return void
     * @uses \App\Controller\RisksController::isAuthorized()
     */
    public function testIsAuthorizedNonSeniorDeveloperAddRisk(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'add');

        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
            'selected_project_role' => 'developer',
            'selected_project' => ['id' => 1],
            'is_admin' => false,
        ]);

        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertFalse($result, "Non-senior developer should not be authorized to add risk.");
    }

    /**
     * Test isAuthorized method for senior developer editing risk in the same project
     *
     * @return void
     * @uses \App\Controller\RisksController::isAuthorized()
     */
    public function testIsAuthorizedSeniorDeveloperEditRiskSameProject(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'edit');

        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
            'selected_project_role' => 'senior_developer',
            'selected_project' => ['id' => 1],
            'is_admin' => false,
        ]);

        $risk = $this->Risks->newEntity([
            'project_id' => 1,
            'description' => 'Test Risk',
            'impact' => 1,
            'category' => 2,
            'probability' => 4,
            'status' => 0,
            'severity' => 3
        ]);
        $savedRisk = $this->Risks->save($risk);
        $this->assertNotNull($savedRisk, "Risk should be saved successfully");

        $request = $request->withParam('pass', [$savedRisk->id]);
        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertTrue($result, "Senior developer should be authorized to edit risk in the same project.");
    }

    /**
     * Test isAuthorized method for admin editing risk in the same project
     *
     * @return void
     * @uses \App\Controller\RisksController::isAuthorized()
     */
    public function testIsAuthorizedAdminEditRiskSameProject(): void
    {
        $request = new ServerRequest();
        $request = $request->withParam('action', 'edit');

        $request->getSession()->write([
            'Auth.User' => ['id' => 2, 'role' => 'user'],
            'selected_project_role' => 'admin',
            'selected_project' => ['id' => 1],
            'is_admin' => true,
        ]);

        $risk = $this->Risks->newEntity([
            'project_id' => 1,
            'description' => 'Test Risk',
            'impact' => 1,
            'category' => 2,
            'probability' => 4,
            'status' => 0,
            'severity' => 3
        ]);
        $savedRisk = $this->Risks->save($risk);
        $this->assertNotNull($savedRisk, "Risk should be saved successfully");

        $request = $request->withParam('pass', [$savedRisk->id]);
        $this->controller->setRequest($request);

        $result = $this->controller->isAuthorized(['role' => 'user']);
        $this->assertTrue($result, "Admin should be authorized to edit risk in the same project.");
    }
    
}
