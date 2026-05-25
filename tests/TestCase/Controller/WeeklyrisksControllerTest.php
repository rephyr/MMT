<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\WeeklyrisksController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\Http\Response;
use Cake\Http\Session;
/**
 * App\Controller\WeeklyrisksController Test Case
 *
 * @uses \App\Controller\WeeklyrisksController
 */
class WeeklyrisksControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Weeklyrisks',
        'app.Risks',
        'app.Weeklyreports',
        'app.Projects',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->session(['Auth.User' => ['id' => 1, 
                        'role' => 'admin'], 
                        'selected_project' => [
                            'id' => 1
                        ],
                        'current_weeklyreport' => [
                            'id' => 1
                        ],
                        'is_admin' => true]);
    }
    // the php translator gives either error for addMethods() or onlyMethods() 
    // i don't know if the test in itself will work since it gives only errors
    // /**
    //  * Test edit method
    //  *
    //  * @return void
    //  * @uses \App\Controller\WeeklyrisksController::edit()
    //  */
    // public function testEditRiskSuccess()
    // {
    //     $mockWeeklyrisks = $this->createMock(\App\Model\Table\WeeklyrisksTable::class);

    //     $riskEntity = new Entity([
    //         'id' => 1,
    //         'risk_id' => 123,
    //         'weeklyreport_id' => 456
    //     ]);
    //     $mockWeeklyrisks->method('get')->willReturn($riskEntity);

    //     $mockSession = $this->createMock(Session::class);
    //     $mockSession->expects($this->once())
    //         ->method('write')
    //         ->with('selected_risk_description', 'Test Risk Description');

    //     $controller = $this->getMockBuilder(WeeklyrisksController::class)
    //         ->addMethods(['Flash', 'redirect', 'getRequest', 'getResponse', 'getSession'])
    //         ->getMock();

    //     $controller->Weeklyrisks = $mockWeeklyrisks;
    //     $controller->method('getSession')->willReturn($mockSession);

    //     $request = $this->createMock(ServerRequest::class);
    //     $request->method('is')->with(['patch', 'post', 'put'])->willReturn(true);
    //     $request->method('getData')->willReturn(['severity' => 3, 'probability' => 5]);
    //     $controller->setRequest($request);

    //     $patchedEntity = clone $riskEntity;
    //     $patchedEntity->set('severity', 3);
    //     $patchedEntity->set('probability', 5);
    //     $mockWeeklyrisks->method('patchEntity')->willReturn($patchedEntity);
    //     $mockWeeklyrisks->method('save')->willReturn(true);

    //     $response = $this->createMock(Response::class);
    //     $controller->method('getResponse')->willReturn($response);

    //     $result = $controller->edit(1);
    //     $this->assertNotNull($result);
    // }
    
    // public function testEditValidationError(): void
    // {
    //     $this->session(['Auth.User' => ['id' => 1, 'role' => 'admin']]);

    //     $data = [
    //         'risk_id' => 1,
    //         'weeklyreport_id' => 1,
    //         'description' => '',
    //         'severity' => 'High',
    //         'probability' => 'Medium',
    //     ];

    //     $this->post('/weeklyrisks/edit/1', $data);

    //     $this->assertResponseSuccess();
    //     $this->assertFlashMessage('The risk could not be saved. Please, try again.');
    // }

    // /**
    //  * Test export method
    //  *
    //  * @return void
    //  * @uses \App\Controller\WeeklyrisksController::export()
    //  */
    /**
     * Test export method
     *
     * @return void
     * @uses \App\Controller\WeeklyrisksController::export()
     */
    public function testExport(): void
    {
        $mockRisksTable = $this->createMock(\App\Model\Table\RisksTable::class);
        $mockRisksTable->method('get')->willReturn(new \Cake\ORM\Entity([
            'project' => ['project_name' => 'Test Project'],
            'description' => 'Test Description'
        ]));
        TableRegistry::set('Risks', $mockRisksTable);

        $this->get('/weeklyrisks/export');

        $this->assertResponseOk();
        $this->assertHeader('Content-Disposition', 'attachment; filename="risks_export.csv"');
        $this->assertContentType('text/csv');
        $actualCsv = (string)$this->_response->getBody();

        $expectedCsv = "project_name,description,probability,severity,week,year\n";
        $expectedCsv .= "\"Test Project\",\"Test Description\",3,0,1,2015\n"; 

        $actualCsv = (string)$this->_response->getBody();

        $this->assertStringContainsString($expectedCsv, $actualCsv);
    }
}
