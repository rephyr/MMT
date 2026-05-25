<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ChartsController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ChartsController Test Case
 *
 * @uses \App\Controller\ChartsController
 */
class ChartsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Projects',
        'app.Users',
        'app.Weeklyreports',
        'app.Members',
        'app.Risks',
    ];

    public function setUp(): void {
        parent::setUp();
        // Set auth and other necessary session data
        $this->session([
            'Auth' => [
                'User' => [
                    'role' => 'admin',
                    'id' => 1,
                ]
            ],
            'is_admin' => true,
            'selected_project' => ['id' => 1, 
                                'finished_date' => '2015-10-25', 
                                'created_on' => '2015-10-22' ],]);
        
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }
    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\ChartsController::index()
     */
    public function testPostRequestWithMoreThan52Weeks(): void
    {
        $data = [
            'weekmin' => 1,
            'weekmax' => 54, 
            'yearmin' => 2015,
            'yearmax' => 2016,
        ];
        $this->enableRetainFlashMessages();
        $this->post('/charts/index', $data);
        $this->assertResponseOk();
        $this->assertFlashMessage('Can\'t display more than 52 weeks');    
    }
    /**
     * Test post request with min year greater than max year
     *
     * @return void
     * @uses \App\Controller\ChartsController::index()
     */
    public function testPostRequestWithMinYearGreaterThanMaxYear(): void    
    {
        $data = [
            'weekmin' => 1,
            'weekmax' => 52,
            'yearmin' => 2016,
            'yearmax' => 2015,
        ];
    
        // Add created_on and finished_date to the sessio
    
        $this->enableRetainFlashMessages();
        $this->post('/charts/index', $data);
        $this->assertResponseOk();
        $this->assertFlashMessage('Min year can\'t be more than max year', 'flash', 'error');
    }
    /**
     * Test post request with min week greater than max week
     *
     * @return void
     * @uses \App\Controller\ChartsController::index()
     */
    public function testPostRequestWithMinWeekGreaterThanMaxWeek(): void
    {    
        $data = [
            'weekmin' => 2,
            'weekmax' => 1,
            'yearmin' => 2015,
            'yearmax' => 2015,
        ];
    
        $this->enableRetainFlashMessages();
        $this->post('/charts/index', $data);
        $this->assertResponseOk();
        $this->assertFlashMessage('Min week can\'t be more than max week', 'flash', 'error');
    }
    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\ChartsController::index()
     */
    public function testIndexSuccess(): void
    {
        // mock the ChartsTable
        $charts = $this->getMockBuilder('App\Model\Table\ChartsTable')
            ->onlyMethods([
                'reports', 'weekList', 'totalhourLineData', 'earnedValueData', 'earnedValueData2', 
                'phaseAreaData', 'reqColumnData', 'commitAreaData', 'testcaseAreaData', 
                'hoursData', 'hoursPerWeekData', 'riskData', 'hoursComparisonData'
            ])
            ->getMock();

        $charts->method('reports')->willReturn(['id' => [1, 2, 3]]);
        $charts->method('weekList')->willReturn([1, 2, 3, 4, 5]);
        $charts->method('earnedValueData')->willReturn([100, 150, 200]);
        $charts->method('earnedValueData2')->willReturn([120, 180, 240]);
        $charts->method('phaseAreaData')->willReturn([10, 20, 30]);
        $charts->method('reqColumnData')->willReturn([5, 10, 15]);
        $charts->method('commitAreaData')->willReturn([3, 6, 9]);
        $charts->method('testcaseAreaData')->willReturn(['testsPassed' => [1, 2, 3], 'testsTotal' => [4, 5, 6]]);
        $charts->method('hoursData')->willReturn([2, 4, 6, 8, 10, 12, 14, 16, 18]);
        $charts->method('hoursPerWeekData')->willReturn([20, 40, 60]);
        $charts->method('riskData')->willReturn([1, 2, 3]);
        $charts->method('hoursComparisonData')->willReturn([5, 10, 15]);

        $data = [
            'weekmin' => 1,
            'weekmax' => 52,
            'yearmin' => 2015,
            'yearmax' => 2015,
        ];

        $this->post('/charts/index', $data);
        $this->assertResponseOk();
        $this->assertEquals('1', $_SESSION['chartLimits']['weekmin']);
        $this->assertEquals('52', $_SESSION['chartLimits']['weekmax']);
        $this->assertEquals('2015', $_SESSION['chartLimits']['yearmin']);
        $this->assertEquals('2015', $_SESSION['chartLimits']['yearmax']);

        $this->assertNotEmpty($_SESSION['weeklyreports'], 'weeklyreports session data is missing or empty.');
        $this->assertNotEmpty($_SESSION['allTheWeeksData'], 'allTheWeeksData session data is missing or empty.');
        $this->assertNotEmpty($_SESSION['phaseData'], 'phaseData session data is missing or empty.');
        $this->assertNotEmpty($_SESSION['reqData'], 'reqData session data is missing or empty.');
        $this->assertNotEmpty($_SESSION['commitData'], 'commitData session data is missing or empty.');
        $this->assertNotEmpty($_SESSION['testcasedata'], 'testcasedata session data is missing or empty.');
        $this->assertNotEmpty($_SESSION['hoursData'], 'hoursData session data is missing or empty.');
        $this->assertNotEmpty($_SESSION['hoursData_1'], 'hoursData_1 session data is missing or empty.');
        $this->assertNotEmpty($_SESSION['hoursperweekdata'], 'hoursperweekdata session data is missing or empty.');
        $this->assertNotEmpty($_SESSION['riskData'], 'riskData session data is missing or empty.');
        $this->assertNotEmpty($_SESSION['hoursComparisonData'], 'hoursComparisonData session data is missing or empty.');
        $this->assertNotEmpty($_SESSION['allTheWeeksData'], 'allTheWeeksData session data is missing or empty (duplicate check).');
        
    }

    /**
     * Test isAuthorized method
     *
     * @return void
     * @uses \App\Controller\ChartsController::isAuthorized()
     */
    public function testIsAuthorized(): void
    {
        $controller = new ChartsController();
        $user = ['id' => 1, 'role' => 'admin'];
        $result = $controller->isAuthorized($user);
        $this->assertTrue($result);
    }
}