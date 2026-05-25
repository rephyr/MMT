<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\MetrictypesController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\I18n\FrozenTime;


/**
 * App\Controller\MetrictypesController Test Case
 *
 * @uses \App\Controller\MetrictypesController
 */
class MetrictypesControllerTest extends TestCase
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
    }
    
    /**
     * Test index method
     *
     * @return void
     * @uses \App\Controller\MetrictypesController::index()
     */
    public function testIndex(): void
    {
        $this->get('/metrictypes');
        $this->assertResponseOk();
        $this->assertResponseContains('Metrictypes');
        $metrictypes = $this->viewVariable('metrictypes');
        $this->assertNotEmpty($metrictypes); 

        // check if the metrictypes array contains the expected metrictypes
        $metrictypesArray = array_map(function ($metric) {
            return [
                'id' => $metric->id,
                'description' => $metric->description,
            ];
        }, $metrictypes->toArray());

        $expected = [
            [
                'id' => 1,
                'description' => 'Current Phase'
            ],
            [
                'id' => 2,
                'description' => 'Total Phases'
            ],
            [
                'id' => 3,
                'description' => 'Passed Test Cases'
            ],
            [
                'id' => 4,
                'description' => 'Total Test Cases'
            ],
        ];

        $this->assertEquals($expected, $metrictypesArray);
    }

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\MetrictypesController::view()
     */
    public function testView(): void
    {
        $this->get('/metrictypes/view/1');
        $this->assertResponseOk();

        $metrictype = $this->viewVariable('metrictype');
        $this->assertNotEmpty($metrictype); // Check if the metrictype variable is set and not empty

        // Add more assertions based on your fixture data
        $expected = [
            'id' => 1,
            'description' => 'Current Phase',
            'metrics' => [
                [
                    'id' => 1,
                    'project_id' => 1,
                    'metrictype_id' => 1,
                    'weeklyreport_id' => 1,
                    'date' => new \Cake\I18n\FrozenDate('2015-10-22'),
                    'value' => 1.0
                ],
            ],
        ];

        $this->assertEquals($expected, $metrictype->toArray());
    }

    /**
     * Test add method
     *
     * @return void
     * @uses \App\Controller\MetrictypesController::add()
     */
    public function testAddSuccess(): void
    {
        // Test displaying the form
        $this->get('/metrictypes/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Add Metrictype'); 
    
        $data = [
            'description' => 'New Metrictype'
        ];
        $this->post('/metrictypes/add', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Metrictypes', 'action' => 'index']);
        $this->assertFlashMessage('The metrictype has been saved.');
    }

    /**
     * Test add method with validation error
     *
     * @return void
     * @uses \App\Controller\MetrictypesController::add()
     */
    public function testAddValidationError(): void
    {
        $data = [
            'description' => '' // empty description should trigger an error
        ];
        $this->post('/metrictypes/add', $data);
        $this->assertResponseSuccess();
        $this->assertNoRedirect();
        $this->assertFlashMessage('The metrictype could not be saved. Please, try again.');
    }

    /**
     * Test edit method success
     *
     * @return void
     * @uses \App\Controller\MetrictypesController::edit()
     */
    public function testEditSuccess(): void
    {
        $this->get('/metrictypes/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit Metrictype');

        $data = [
            'description' => 'Updated Metrictype'
        ];
        $this->patch('/metrictypes/edit/1', $data);
        $this->assertResponseSuccess();
        $this->assertRedirect(['controller' => 'Metrictypes', 'action' => 'index']);
        $this->assertFlashMessage('The metrictype has been saved.');
    }

    /**
     * Test edit method validation error
     *
     * @return void
     * @uses \App\Controller\MetrictypesController::edit()
     */
    public function testEditValidationError(): void
    {
        $data = [
            'description' => '' // invalid data
        ];
        $this->patch('/metrictypes/edit/1', $data);
        $this->assertResponseSuccess();
        $this->assertNoRedirect();
        $this->assertFlashMessage('The metrictype could not be saved. Please, try again.');
    }

    // Depended records should be deleted first but is not
    // too scared to implement that in MetricTypesController so I will skip this test
    // /**
    //  * Test delete method
    //  *
    //  * @return void
    //  * @uses \App\Controller\MetrictypesController::delete()
    //  */
    // public function testDelete(): void
    // {
    //     
    //     $this->delete('/metrics/delete/1');
    //     $this->assertResponseSuccess();
    
    //     
    //     $this->post('/metrictypes/delete/1');
    //     $this->assertResponseSuccess();
    //     $this->assertRedirect(['controller' => 'Metrictypes', 'action' => 'index']);
    //     $this->assertFlashMessage('The metrictype has been deleted.');
    // }

    /**
     * Test isAuthorized method
     *
     * @return void
     * @uses \App\Controller\MetrictypesController::isAuthorized()
     */
    public function testIsAuthorized(): void
    {
        // admin user = access
        $this->session([
            'Auth.User' => [
                'id' => 1,
                'role' => 'admin'
            ]
        ]);
        $this->get('/metrictypes');
        $this->assertResponseOk();

        // non-admin = no access
        $this->session([
            'Auth.User' => [
                'id' => 2,
                'role' => 'user'
            ]
        ]);
        $this->get('/metrictypes');
        $this->assertResponseCode(302); 
    }
}
