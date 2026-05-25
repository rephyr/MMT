<?php
namespace App\Test\TestCase\Controller;

use App\Controller\MetricsController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\I18n\FrozenTime;


/**
 * App\Controller\MetricsController Test Case
 *
 * @uses \App\Controller\MetricsController
 */
class MetricsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Metrics',
        'app.Projects',
        'app.Metrictypes',
        'app.Weeklyreports',
    ];
    private $Metrics;
    
    public function setUp(): void
    {   
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();

        $this->session(['Auth.User' => ['id' => 1, 
                        'role' => 'admin'], 
                        'selected_project' => ['id' => 1], 
                        'current_weeklyreport' => ['id' => 1, 'created_on' => '2015-10-22'],
                        'is_admin' => true]);
        $this->Metrics = $this->getTableLocator()->get('Metrics');
        
    }

    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\MetricsController::index()
     */
    public function testIndex(): void
    {
        $this->get('/metrics/index');
        $this->assertResponseOk();
        $metrics = $this->viewVariable('metrics');
        $this->assertNotEmpty($metrics);
        // check if the metrics are sorted by date
        $dates = array_map(function ($metric) {
            return $metric->date;
        }, $metrics->toArray());
        $sortedDates = $dates;
        rsort($sortedDates);
        $this->assertEquals($sortedDates, $dates);
        
        // check if the metrics are from the selected project
        foreach ($metrics as $metric) {
            $this->assertEquals(1, $metric->project_id);
        }
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\MetricsController::view()
     */
    public function testView(): void
    {
        $this->get('/metrics/view/1');

        $this->assertResponseOk();

        $metric = $this->viewVariable('metric');
        $this->assertNotEmpty($metric);

        $this->assertEquals(1, $metric->project_id);
    }

    /**
     * Test add method
     *
     * @return void
     * @uses \App\Controller\MetricsController::add()
     */
    public function testAddSuccess(): void
    {
        $data = [
            'id' => 2,
            'value' => 1,
            'project_id' => 1,
            'metrictype_id' => 1,
            'date' => '2023-10-10'
        ];

        $this->post('/metrics/add', $data);
        $this->assertResponseSuccess();
        $this->assertFlashMessage('The metric has been saved.');

        $metricsTable = $this->getTableLocator()->get('Metrics');
        $metric = $metricsTable->find()->where(['id' => 2])->first();
        $this->assertNotEmpty($metric);

        $this->assertEquals(1, $metric->project_id);
        $this->assertNull($metric->weeklyreport_id);
    }

        /**
     * Test add method with validation errors
     *
     * @return void
     * @uses \App\Controller\MetricsController::add()
     */
    public function testAddValidationErrors(): void
    {
        $data = [
            'id' => 2,
            'value' => '', // value is required should trigger a validation error
            'project_id' => 1,
            'metrictype_id' => 1,
            'date' => '2023-10-10'
        ];

        $this->post('/metrics/add', $data);

        $this->assertResponseSuccess();
        $this->assertFlashMessage('The metric could not be saved. Please, try again.');

        $metricsTable = $this->getTableLocator()->get('Metrics');
        $metric = $metricsTable->find()->where(['id' => 2])->first();
        $this->assertEmpty($metric);
    }

    /**
     * Test add method with database save failure
     *
     * @return void
     * @uses \App\Controller\MetricsController::add()
     */
    public function testAddSaveFailure(): void
    {
        $metricsTable = $this->getMockForModel('Metrics', ['save']);
        $metricsTable->expects($this->once())
            ->method('save')
            ->will($this->returnValue(false));

        $data = [
            'id' => 2,
            'value' => 1,
            'project_id' => 1,
            'metrictype_id' => 1,
            'date' => '2023-10-10'
        ];

        $this->post('/metrics/add', $data);

        $this->assertResponseSuccess();
        $this->assertFlashMessage('The metric could not be saved. Please, try again.');

        $metricsTable = $this->getTableLocator()->get('Metrics');
        $metric = $metricsTable->find()->where(['id' => 2])->first();
        $this->assertEmpty($metric);
    }


    /**
     * Test addadmin method
     *
     * @return void
     * @uses \App\Controller\MetricsController::addadmin()
     */
    public function testAddAdmin(): void
    {
        $data = [
            'id' => 2,
            'value' => 1,
            'project_id' => 1,
            'metrictype_id' => 1,
            'date' => '2023-10-10',
            'weeklyreport_id' => 1
        ];

        $this->post('/metrics/addadmin', $data);

        $this->assertResponseSuccess();
        $this->assertFlashMessage('The metric has been saved.');

        $metricsTable = $this->getTableLocator()->get('Metrics');
        $metric = $metricsTable->find()->where(['id' => 2])->first();
        $this->assertNotEmpty($metric);

        $this->assertEquals(1, $metric->project_id);
    }

    /**
     * Test addadmin method with validation errors
     *
     * @return void
     * @uses \App\Controller\MetricsController::addadmin()
     */
    public function testAddAdminValidationErrors(): void
    {
        $data = [
            'id' => 2,
            'value' => '', // trigger validation error
            'project_id' => 1,
            'metrictype_id' => 1,
            'date' => '2023-10-10',
            'weeklyreport_id' => 1
        ];

        $this->post('/metrics/addadmin', $data);

        $this->assertResponseSuccess();
        $this->assertFlashMessage('The metric could not be saved. Please, try again.');

        $metricsTable = $this->getTableLocator()->get('Metrics');
        $metric = $metricsTable->find()->where(['id' => 2])->first();
        $this->assertEmpty($metric);
    }

    /**
     * Test addmultiple method
     *
     * @return void
     * @uses \App\Controller\MetricsController::addmultiple()
     */
    public function testAddMultiple(): void
    {
        // multiple metrics data
        $data = [
            'phase' => 5,
            'totalPhases' => 10,
            'reqNew' => 3,
            'reqInProgress' => 2,
            'reqClosed' => 4,
            'reqRejected' => 1,
            'commits' => 20,
            'passedTestCases' => 15,
            'totalTestCases' => 20,
            'degreeReadiness' => 80,
            'overallStatus' => 90,
            'submit' => 'next'
        ];

        $this->post('/metrics/addmultiple', $data);
        $this->assertResponseSuccess();

        //check that the metrics were added to the session
        $metrics = $this->getSession()->read('current_metrics');
        $this->assertNotEmpty($metrics);
        $this->assertCount(11, $metrics);

        // check if the metrics are from the selected project
        foreach ($metrics as $metric) {
            $this->assertEquals(1, $metric->project_id);
        }
        $this->assertRedirect(['controller' => 'Risks', 'action' => 'addweekly']);

    }

    /**
     * Test addmultiple method with validation errors
     *
     * @return void
     * @uses \App\Controller\MetricsController::addmultiple()
     */
    public function testAddMultipleValidationErrors(): void
    {
        $data = [
            'phase' => -1, // validation error
            'totalPhases' => 10,
            'reqNew' => 3,
            'reqInProgress' => 2,
            'reqClosed' => 4,
            'reqRejected' => 1,
            'commits' => 20,
            'passedTestCases' => 15,
            'totalTestCases' => 20,
            'degreeReadiness' => 80,
            'overallStatus' => 90,
            'submit' => 'next'
        ];

        $this->post('/metrics/addmultiple', $data);

        $this->assertResponseSuccess();
        $this->assertFlashMessage('Make sure all fields are filled with values greater than zero');

        $metrics = $this->getSession()->read('current_metrics');
        $this->assertEmpty($metrics);
    }

    /**
     * Test addmultiple method with phase validation errors
     *
     * @return void
     * @uses \App\Controller\MetricsController::addmultiple()
     */
    public function testAddMultiplePhaseValidationErrors(): void
    {
        $data = [
            'phase' => 15, // plhase greater than totalPhases 
            'totalPhases' => 10,
            'reqNew' => 3,
            'reqInProgress' => 2,
            'reqClosed' => 4,
            'reqRejected' => 1,
            'commits' => 20,
            'passedTestCases' => 15,
            'totalTestCases' => 20,
            'degreeReadiness' => 80,
            'overallStatus' => 90,
            'submit' => 'next'
        ];

        $this->post('/metrics/addmultiple', $data);

        $this->assertResponseSuccess();
        $this->assertFlashMessage('Current phase number cannot exceed the total.');

        $metrics = $this->getSession()->read('current_metrics');
        $this->assertEmpty($metrics);
    }

    public function testEditSuccess(): void
    {

        $metric = $this->Metrics->get(1);
        $editData = ['value' => 5];

        $this->put('/metrics/edit/1', $editData);
        $this->assertResponseSuccess();
        $this->assertFlashMessage('The metric has been saved.');

        $updatedMetric = $this->Metrics->get(1);
        $this->assertEquals(5, $updatedMetric->value);
    }
       /**
     * Test edit method with validation errors
     *
     * @return void
     * @uses \App\Controller\MetricsController::edit()
     */
    public function testEditValidationErrors(): void
    {
        $metric = $this->Metrics->get(1);
        
        $editData = [
            'metrictype_id' => 12, // invalid metrictype_id highest is 11
            'value' => 1,
            'project_id' => 1,
            'weeklyreport_id' => 1, 
            'date' => '2015-10-22',
        ];

        $this->put('/metrics/edit/1', $editData);
        $this->assertResponseSuccess();
        $this->assertFlashMessage('The metric could not be saved. Please, try again.');
    }

    /**
     * Test edit method with phase constraints
     *
     * @return void
     * @uses \App\Controller\MetricsController::edit()
     */
    public function testEditPhaseConstraints(): void
    {
        $metric = $this->Metrics->get(1);
        
        $existingData = [
            'metrictype_id' => 2, // total phases
            'value' => 5,
            'project_id' => 1,
            'weeklyreport_id' => 1, 
            'date' => '2015-10-22',
        ];

        $metrics = $this->getTableLocator()->get('Metrics');
        $existingMetric = $metrics->newEntity($existingData);
        if (!$metrics->save($existingMetric)) {
            debug($existingMetric->getErrors());
        }
    
        $editData = [
            'value' => 6, // higher than total phases
        ];
    
        $this->put('/metrics/edit/1', $editData);
        $this->assertResponseSuccess();
        $this->assertFlashMessage("Current phase can't be higher than total number of planned phases. Please, try again.");
    }

    /**
     * Test edit method with test case constraints
     *
     * @return void
     * @uses \App\Controller\MetricsController::edit()
     */
    public function testEditTestCaseConstraints(): void
    {
        $existingData = [
            'metrictype_id' => 9, // total test cases
            'value' => 10,
            'project_id' => 1,
            'weeklyreport_id' => 1,
            'created_on' => FrozenTime::now(),
        ];
        $metrics = $this->getTableLocator()->get('Metrics');
        $existingMetric = $metrics->newEntity($existingData);
        $metrics->save($existingMetric);

        $metric = $this->Metrics->get(1);
        $editData = [
            'metrictype_id' => 8, // passed test cases
            'value' => 11, // higher than total test cases
        ];

        $this->put('/metrics/edit/1', $editData);
        $this->assertResponseSuccess();
    }

    /**
     * Test delete method for a metric that does not belong to a weekly report
     *
     * @return void
     * @uses \App\Controller\MetricsController::delete()
     */
    public function testDeleteSuccess(): void
    {
        $metric = $this->Metrics->get(1);
        $metric->weeklyreport_id = null;
        $this->Metrics->save($metric);

        $this->delete('/metrics/delete/1');
        $this->assertResponseSuccess();
        $this->assertFlashMessage('The metric has been deleted.');
    }

    /**
     * Test delete method for a metric that belongs to a weekly report
     *
     * @return void
     * @uses \App\Controller\MetricsController::delete()
     */
    public function testDeleteFailure(): void
    {
        $metric = $this->Metrics->get(1);
        $metric->weeklyreport_id = 1;
        $this->Metrics->save($metric);

        $this->delete('/metrics/delete/1');
        $this->assertResponseSuccess();
        $this->assertFlashMessage('Cannot delete metrics that belong to a weeklyreport');
    }

    /**
     * Test deleteadmin method for a metric as an admin
     *
     * @return void
     * @uses \App\Controller\MetricsController::deleteadmin()
     */
    public function testDeleteAdminSuccess(): void
    {
        // ensure the record to be deleted exists
        $metric = $this->Metrics->get(1);

        $this->post('/metrics/deleteadmin/1');
        $this->assertResponseSuccess();
        $this->assertFlashMessage('The metric has been deleted.');
    }

    /**
     * Test getMetricNames method
     *
     * @return void
     * @uses \App\Controller\MetricsController::getMetricNames()
     */
    public function testGetMetricNames(): void
    {
        $controller = new MetricsController();
        $metricNames = $controller->getMetricNames();

    $expected = [
            1 => 'Current phase',
            2 => 'Total number of planned phases',
            3 => 'Product backlog',
            4 => 'Sprint backlog',
            5 => 'Done',
            6 => 'Rejected',
            7 => 'Commits in total',
            8 => 'Passed test cases',
            9 => 'Total number of test cases',
            10 => 'Degree of readiness',
            11 => 'Overall status',
        ];

        $this->assertEquals($expected, $metricNames);
    }

    /**
     * Test isAuthorized method for an admin user
     *
     * @return void
     * @uses \App\Controller\MetricsController::isAuthorized()
     */
    public function testIsAuthorizedAdmin(): void
    {
        $controller = new MetricsController();
        $user = ['role' => 'admin'];
        $result = $controller->isAuthorized($user);
        $this->assertTrue($result);
    }

    /**
     * Test isAuthorized method for a non-admin user
     *
     * @return void
     * @uses \App\Controller\MetricsController::isAuthorized()
     */
    public function testIsAuthorizedNonAdmin(): void
    {
        $controller = new MetricsController();
        $user = ['role' => 'user'];
        $result = $controller->isAuthorized($user);
        $this->assertFalse($result);
    }
}
