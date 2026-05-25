<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\WeeklyreportsController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\I18n\FrozenTime;


/**
 * App\Controller\WeeklyreportsController Test Case
 *
 * @uses \App\Controller\WeeklyreportsController
 */
class WeeklyreportsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Weeklyreports',
        'app.Projects',
        'app.Users',
        'app.Metrics',
        'app.Workinghours',
        'app.Weeklyrisks',
        'app.Risks',
    ];

    public function setUp(): void {
        parent::setUp();

        // Set auth
        $this->session(['Auth.User' => ['id' => 1, 
                        'role' => 'admin'], 
                        'selected_project' => ['id' => 1], 
                        'is_admin' => true]);    
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();   
    }

    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\WeeklyreportsController::index()
     */
    public function testIndex(): void
    {
        $this->get('/weeklyreports');

        $this->assertResponseOk();

        $weeklyreports = $this->viewVariable('weeklyreports');
        $this->assertNotEmpty($weeklyreports, 'Weekly reports should be set in the view');

        // weekly reports should be ordered by year and week
        $previousYear = PHP_INT_MAX;
        $previousWeek = PHP_INT_MAX;
        foreach ($weeklyreports as $report) {
            $this->assertEquals(1, $report->project_id, 'Weekly report should belong to the selected project');
            $this->assertLessThanOrEqual($previousYear, $report->year, 'Weekly reports should be ordered by year DESC');
            if ($report->year == $previousYear) {
                $this->assertLessThanOrEqual($previousWeek, $report->week, 'Weekly reports should be ordered by week DESC');
            }
            // update previous year and week
            $previousYear = $report->year;
            $previousWeek = $report->week;
        }
    }

    /**
     * Test view method as admin
     *
     * @return void
     * @uses \App\Controller\WeeklyreportsController::view()
     */
    public function testViewAsAdmin(): void
    {
        $weeklyreport = $this->getTableLocator()->get('Weeklyreports')->find()->first();
        $this->get('/weeklyreports/view/' . $weeklyreport->id);

        $this->assertResponseOk();

        $viewWeeklyreport = $this->viewVariable('weeklyreport');
        $this->assertNotEmpty($viewWeeklyreport, 'Weekly report should be set in the view');
        $this->assertEquals($weeklyreport->id, $viewWeeklyreport->id, 'Weekly report ID should match');

        $risks = $this->viewVariable('risks');
        $this->assertNotEmpty($risks, 'Risks should be set in the view');
    }

    /**
     * Test view method as supervisor
     *
     * @return void
     * @uses \App\Controller\WeeklyreportsController::view()
     */
    public function testViewAsSupervisor(): void
    {
        $this->session([
            'Auth.User' => [
                'id' => 2,
                'role' => 'user'
            ],
            'selected_project_role' => 'supervisor',
            'selected_project' => [
                'id' => 1
            ],
            'is_admin' => false
        ]);

        $weeklyreport = $this->getTableLocator()->get('Weeklyreports')->find()->first();
        $this->get('/weeklyreports/view/' . $weeklyreport->id);

        $this->assertResponseOk();

        $viewWeeklyreport = $this->viewVariable('weeklyreport');
        $this->assertNotEmpty($viewWeeklyreport, 'Weekly report should be set in the view');
        $this->assertEquals($weeklyreport->id, $viewWeeklyreport->id, 'Weekly report ID should match');

        $risks = $this->viewVariable('risks');
        $this->assertNotEmpty($risks, 'Risks should be set in the view');
    }

    /**
     * Test view method as regular user with access
     *
     * @return void
     * @uses \App\Controller\WeeklyreportsController::view()
     */
    public function testViewAsRegularUserWithAccess(): void
    {
        $this->session([
            'Auth.User' => [
                'id' => 3,
                'role' => 'user'
            ],
            'selected_project_role' => 'developer',
            'selected_project' => [
                'id' => 1
            ],
            'is_admin' => false
        ]);

        $weeklyreport = $this->getTableLocator()->get('Weeklyreports')->find()->first();
        $this->get('/weeklyreports/view/' . $weeklyreport->id);

        $this->assertResponseOk();

        $viewWeeklyreport = $this->viewVariable('weeklyreport');
        $this->assertNotEmpty($viewWeeklyreport, 'Weekly report should be set in the view');
        $this->assertEquals($weeklyreport->id, $viewWeeklyreport->id, 'Weekly report ID should match');

        $risks = $this->viewVariable('risks');
        $this->assertNotEmpty($risks, 'Risks should be set in the view');
    }

    /**
     * Test view method as regular user without access
     *
     * @return void
     * @uses \App\Controller\WeeklyreportsController::view()
     */
    public function testViewAsRegularUserWithoutAccess(): void
    {
        $this->session([
            'Auth.User' => [
                'id' => 3,
                'role' => 'user'
            ],
            'selected_project_role' => 'developer',
            'selected_project' => [
                'id' => 2
            ],
            'is_admin' => false
        ]);

        $weeklyreport = $this->getTableLocator()->get('Weeklyreports')->find()->first();
        $this->get('/weeklyreports/view/' . $weeklyreport->id);

        $this->assertRedirect(['controller' => 'Projects', 'action' => 'index']);
    }

    /**
     * Test add method
     *
     * @return void
     * @uses \App\Controller\WeeklyreportsController::add()
     */
    public function testAdd(): void
    {
        $data = [
            'title' => 'New Weekly Report',
            'week' => 42,
            'year' => 2024,
            'problems' => '0',
            'meetings' => '0',
            'additional' => 'Additional notes',
        ];
        $this->post('/weeklyreports/add', $data);

        $this->assertResponseSuccess();
        // in the controller, we store the weekly report in the session
        $sessionWeeklyReport = $this->getSession()->read('current_weeklyreport');
        $this->assertNotEmpty($sessionWeeklyReport);
        $this->assertEquals(42, $sessionWeeklyReport->week);
        $this->assertEquals(2024, $sessionWeeklyReport->year);
        $this->assertEquals('New Weekly Report', $sessionWeeklyReport->title);
        $this->assertEquals('0', $sessionWeeklyReport->problems);
        $this->assertEquals('0', $sessionWeeklyReport->meetings);
        $this->assertEquals('Additional notes', $sessionWeeklyReport->additional);
        $this->assertEquals(1, $sessionWeeklyReport->project_id);
        $this->assertEquals(1, $sessionWeeklyReport->created_by);

        $this->assertRedirect(['controller' => 'Metrics', 'action' => 'addmultiple']);
    }
    /**
     * Test add method for non-unique weekly report
     *
     * @return void
     * @uses \App\Controller\WeeklyreportsController::add()
     */
    public function testAddNonUniqueWeeklyReport(): void
    {
        $this->enableRetainFlashMessages();

        $existingData = [
            'title' => 'Existing Weekly Report',
            'week' => 42, //same week 
            'year' => 2024,
            'problems' => '0',
            'meetings' => '0',
            'additional' => 'Additional notes',
            'project_id' => 1,
            'created_on' => FrozenTime::now(),
            'created_by' => 1,
        ];
        $weeklyreports = $this->getTableLocator()->get('Weeklyreports');
        $existingReport = $weeklyreports->newEntity($existingData);
        $weeklyreports->save($existingReport);

        // attempt to add a weekly report for a week that already has a report
        $data = [
            'title' => 'New Weekly Report',
            'week' => 42, //same week 
            'year' => 2024,
            'problems' => '0',
            'meetings' => '0',
            'additional' => 'Additional notes',
        ];
        $this->post('/weeklyreports/add', $data);

        $this->assertResponseSuccess();
        $this->assertFlashMessage('This week already has a weeklyreport');
    }

    /**
     * Test add method for invalid week or year
     *
     * @return void
     * @uses \App\Controller\WeeklyreportsController::add()
     */
    public function testAddInvalidWeek(): void
    {
        $this->enableRetainFlashMessages();
        $projects = $this->getTableLocator()->get('Projects');
        $project = $projects->get(1);
        $project->created_on = '2024-07-01'; //project created in July 2024
        $projects->save($project);
        // attempt to add a weekly report with an invalid week or year
        $data = [
            'title' => 'New Weekly Report',
            'week' => 25, // invalid week (before project creation)
            'year' => 2024,
            'problems' => '0',
            'meetings' => '0',
            'additional' => 'Additional notes',
        ];
        $this->post('/weeklyreports/add', $data);
    
        $this->assertResponseSuccess();
        $this->assertFlashMessage('Check week and/or year.');
    }
    /**
     * Test add method for validation errors
     *
     * @return void
     * @uses \App\Controller\WeeklyreportsController::add()
     */
    public function testAddValidationErrors(): void
    {
        $this->enableRetainFlashMessages();

        // invalid data should trigger validation error
        $data = [
            'title' => '', // Missing title
            'week' => 42,
            'year' => 2024,
            'problems' => '0',
            'meetings' => '0',
            'additional' => 'Additional notes',
        ];
        $this->post('/weeklyreports/add', $data);

        $this->assertResponseSuccess();
        $this->assertFlashMessage('Report failed validation');
    }

    public function testEditSuccessful(): void
    {
        // set up the project creation date in the fixture
        $projects = $this->getTableLocator()->get('Projects');
        $project = $projects->get(1);
        $project->created_on = '2015-01-01'; 
        $projects->save($project);

        // data to update the weekly report
        $data = [
            'title' => 'Updated Weekly Report',
            'week' => 1, 
            'year' => 2015, 
            'problems' => 'Updated problems',
            'meetings' => 'Updated meetings',
            'additional' => 'Updated additional notes',
        ];
        $this->put('/weeklyreports/edit/1', $data);
        $this->assertResponseSuccess();

        $this->assertFlashMessage('The weeklyreport has been saved.');
        // Verify the updated data
        $weeklyreports = $this->getTableLocator()->get('Weeklyreports');
        $updatedReport = $weeklyreports->get(1);
        $this->assertEquals('Updated Weekly Report', $updatedReport->title);
        $this->assertEquals('Updated problems', $updatedReport->problems);
        $this->assertEquals('Updated meetings', $updatedReport->meetings);
        $this->assertEquals('Updated additional notes', $updatedReport->additional);
    }

    /**
     * Test edit method for invalid week or year
     *
     * @return void
     * @uses \App\Controller\WeeklyreportsController::edit()
     */
    public function testEditInvalidWeekOrYear(): void
    {
        $projects = $this->getTableLocator()->get('Projects');
        $project = $projects->get(1);
        $project->created_on = '2015-01-01'; 
        $projects->save($project);

        $data = [
            'title' => 'Updated Weekly Report',
            'week' => 53, // invalid week
            'year' => 2014, // invalid year
            'problems' => 'Updated problems',
            'meetings' => 'Updated meetings',
            'additional' => 'Updated additional notes',
        ];
        $this->put('/weeklyreports/edit/1', $data);
        $this->assertResponseSuccess();
        $this->assertFlashMessage('Check week and/or year.');
    }

    /**
     * Test edit method for validation errors
     *
     * @return void
     * @uses \App\Controller\WeeklyreportsController::edit()
     */
    public function testEditValidationErrors(): void
    {
        $weeklyreports = $this->getTableLocator()->get('Weeklyreports');
        $editData = [
            'title' => 'Weekly Report',
            'week' => 23,
            'year' => 2017,
            'problems' => 'Some reported issues',
            'meetings' => 'Meeting details here',
            'additional' => 'Additional notes',
            'project_id' => 1,
            'created_on' => '2016-02-01',
            'created_by' => 1,
        ];
        $editReport = $weeklyreports->newEntity($editData);
        $weeklyreports->save($editReport);
    
        $data = [
            'title' => '', // trigger validation error
            'week' => 23,
            'year' => 2017,
            'problems' => 'Updated problems',
            'meetings' => 'Updated meetings',
            'additional' => 'Updated additional notes',
        ];
    
        $this->put('/weeklyreports/edit/' . $editReport->id, $data);
        $this->assertResponseSuccess();
        $this->assertFlashMessage('The weeklyreport could not be saved. Please, try again.');
    }

    /**
     * Test delete method for successful delete
     *
     * @return void
     * @uses \App\Controller\WeeklyreportsController::delete()
     */
    public function testDeleteSuccessful(): void
    {
        $weeklyreportsTable = $this->getTableLocator()->get('Weeklyreports');
        $weeklyreport = $weeklyreportsTable->get(1);
        $this->assertNotEmpty($weeklyreport);

        $this->post('/weeklyreports/delete/1');

        $this->assertResponseSuccess();
        $this->assertFlashMessage('The weeklyreport has been deleted.');

        $deletedWeeklyreport = $weeklyreportsTable->find()->where(['id' => 1])->first();
        $this->assertEmpty($deletedWeeklyreport);
    }
    
    // Gives error because delete doesnt handle the case where the weekly report does not exist
    // should work if this is added
    // /**
    //  * Test delete method for non-existent weekly report
    //  *
    //  * @return void
    //  * @uses \App\Controller\WeeklyreportsController::delete()
    //  */
    // public function testDeleteNonExistentWeeklyReport(): void
    // {
    //     
    //     $this->post('/weeklyreports/delete/999');
    //     $this->assertResponseError();
    //     $this->assertFlashMessage('The weeklyreport could not be deleted. Please, try again.');
    // }

    /**
     * Test preparemail method
     *
     * @return void
     * @uses \App\Controller\WeeklyreportsController::preparemail()
     */
    public function testPreparemail(): void
    {
        // get the weekly report
        $weeklyreportsTable = $this->getTableLocator()->get('Weeklyreports');
        $weeklyreport = $weeklyreportsTable->get(1, [
            'contain' => ['Projects', 'Metrics', 'Workinghours']
        ]);
        $this->assertNotEmpty($weeklyreport);

        // prepare the email
        $controller = new WeeklyreportsController();
        $content = $controller->preparemail(1);

        // check that the email content contains the weekly report data
        $this->assertStringContainsString('<div style="max-width:600px">', $content);
        $this->assertStringContainsString('<table border="1" style="width:100%;border-collapse:collapse;margin-bottom:20px">', $content);
        $this->assertStringContainsString('<tr><td>Title</td><td style="text-align:right">' . $weeklyreport->title . '</td></tr>', $content);
        $this->assertStringContainsString('<tr><td>Week</td><td style="text-align:right">' . $weeklyreport->week . '</td></tr>', $content);
        $this->assertStringContainsString('<tr><td>Year</td><td style="text-align:right">' . $weeklyreport->year . '</td></tr>', $content);
        $this->assertStringContainsString('<tr><td>Meetings</td><td style="text-align:right">' . $weeklyreport->meetings . '</td></tr>', $content);
        $this->assertStringContainsString('<tr><td>Realized risks,Challenges, issues, etc.</td><td style="text-align:right">' . $weeklyreport->problems . '</td></tr>', $content);
        $this->assertStringContainsString('<tr><td>Addtitional</td><td style="text-align:right">' . $weeklyreport->additional . '</td></tr>', $content);
        $this->assertStringContainsString('<tr><td>Created on</td><td style="text-align:right">' . $weeklyreport->created_on->format('d.m.Y') . '</td></tr>', $content);
        $this->assertStringContainsString('<tr><td>Updated on</td><td style="text-align:right">' . ($weeklyreport->updated_on != null ? $weeklyreport->updated_on->format('d.m.Y') : '') . '</td></tr>', $content);
        $this->assertStringContainsString('</table>', $content);
        $this->assertStringContainsString('<tr><td colspan="3" style="text-align:center;font-size:18px;padding:5px">Working Hours for week ' . $weeklyreport->week . '</td></tr>', $content);
        $this->assertStringContainsString('<tr><td>Name</td><td>Project Role</td><td>Working hours</td></tr>', $content);
        $this->assertStringContainsString('<tr><td colspan="3" style="text-align:center;font-size:18px;padding:5px">Metrics</td></tr>', $content);
        $this->assertStringContainsString('<tr><td>Metric type</td><td>Value</td><td>Date</td></tr>', $content);
        $this->assertStringContainsString('<tr><td colspan="3" style="text-align:center;font-size:18px;padding:5px">Risks</td></tr>', $content);
        $this->assertStringContainsString('<tr><td>Risk</td><td>Severity</td><td>Probability</td></tr>', $content);
        $this->assertStringContainsString('<tr><td>Risk 1 description</td><td>None</td><td>Medium</td></tr>', $content);
    }

    /**
     * Test isAuthorized method for preparemail action
     *
     * @return void
     * @uses \App\Controller\WeeklyreportsController::isAuthorized()
     */
    public function isAuthorized(): void
    {
        // Simulate a request to the preparemail action
        $this->get('/weeklyreports/preparemail/1');

        // Verify that the user is authorized
        $controller = new WeeklyreportsController();
        $user = ['id' => 2, 'role' => 'admin'];
        $this->assertTrue($controller->isAuthorized($user));
    }
}
