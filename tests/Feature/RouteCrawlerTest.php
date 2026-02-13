<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use App\Models\Ebook;
use App\Models\Category;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

class RouteCrawlerTest extends TestCase
{
    use RefreshDatabase;

    private $testUsers = [];
    private $crawlResults = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users for each role
        $this->testUsers = [
            'admin' => User::factory()->create([
                'email' => 'admin@zenithalms.com',
                'role_id' => 1, // admin role
                'password' => bcrypt('password')
            ]),
            'instructor' => User::factory()->create([
                'email' => 'instructor@zenithalms.com', 
                'role_id' => 2, // instructor role
                'password' => bcrypt('password')
            ]),
            'student' => User::factory()->create([
                'email' => 'student@zenithalms.com',
                'role_id' => 3, // student role  
                'password' => bcrypt('password')
            ]),
            'organization' => User::factory()->create([
                'email' => 'demo@zenithalms.com',
                'role_id' => 4, // organization admin role
                'password' => bcrypt('password')
            ])
        ];

        // Create test data for parameterized routes
        Category::factory()->create(['slug' => 'test-category']);
        Course::factory()->create(['slug' => 'test-course']);
        Ebook::factory()->create(['slug' => 'test-ebook']);
    }

    /**
     * Test all public routes without authentication
     */
    public function test_public_routes_crawl()
    {
        $publicRoutes = [
            '/',
            '/login',
            '/register', 
            '/forgot-password',
            '/courses',
            '/ebooks',
            '/ebooks/test-ebook',
            '/blog',
            '/robots.txt',
            '/sitemap.xml'
        ];

        $results = [];

        foreach ($publicRoutes as $route) {
            try {
                $response = $this->get($route);
                
                $results[$route] = [
                    'status' => $response->getStatusCode(),
                    'success' => $response->getStatusCode() < 400,
                    'redirect' => $response->getStatusCode() === 302 ? $response->headers->get('Location') : null,
                    'error' => $response->getStatusCode() >= 400 ? 'HTTP ' . $response->getStatusCode() : null
                ];
            } catch (\Exception $e) {
                $results[$route] = [
                    'status' => 500,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        $this->crawlResults['public'] = $results;
        
        // Log results for report
        Log::info('Public routes crawl completed', ['results' => $results]);
        
        // Assert no critical failures
        $failures = collect($results)->filter(fn($r) => !$r['success'] && $r['status'] >= 500);
        $this->assertEmpty($failures, 'Critical failures found in public routes: ' . json_encode($failures->toArray()));
    }

    /**
     * Test authenticated routes with each user role
     */
    public function test_authenticated_routes_crawl()
    {
        $roleRoutes = [
            'admin' => [
                '/dashboard',
                '/dashboard/admin',
                '/profile',
                '/system/analytics',
                '/system/reports',
                '/system/settings',
                '/system/users',
                '/ebooks/admin',
                '/ebooks/admin/1'
            ],
            'instructor' => [
                '/dashboard',
                '/dashboard/instructor', 
                '/profile',
                '/ebooks/create',
                '/ebooks/my-ebooks'
            ],
            'student' => [
                '/dashboard',
                '/dashboard/student',
                '/profile',
                '/ebooks/my-ebooks',
                '/ebooks/read/1',
                '/ebooks/download/1'
            ],
            'organization' => [
                '/dashboard',
                '/dashboard/organization',
                '/profile'
            ]
        ];

        foreach ($roleRoutes as $role => $routes) {
            $user = $this->testUsers[$role];
            $results = [];

            foreach ($routes as $route) {
                try {
                    $response = $this->actingAs($user)->get($route);
                    
                    $results[$route] = [
                        'status' => $response->getStatusCode(),
                        'success' => $response->getStatusCode() < 400,
                        'redirect' => $response->getStatusCode() === 302 ? $response->headers->get('Location') : null,
                        'error' => $response->getStatusCode() >= 400 ? 'HTTP ' . $response->getStatusCode() : null
                    ];
                } catch (\Exception $e) {
                    $results[$route] = [
                        'status' => 500,
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }

            $this->crawlResults[$role] = $results;
            Log::info("Authenticated routes crawl completed for role: {$role}", ['results' => $results]);
            
            // Assert no critical failures for this role
            $failures = collect($results)->filter(fn($r) => !$r['success'] && $r['status'] >= 500);
            $this->assertEmpty($failures, "Critical failures found for {$role} routes: " . json_encode($failures->toArray()));
        }
    }

    /**
     * Test API endpoints
     */
    public function test_api_routes_crawl()
    {
        $publicApiRoutes = [
            '/api/v1/health',
            '/api/v1/courses',
            '/api/v1/courses/test-course',
            '/api/v1/search'
        ];

        $protectedApiRoutes = [
            '/api/v1/auth/user',
            '/api/v1/user/dashboard',
            '/api/v1/user/profile',
            '/api/v1/notifications'
        ];

        $results = [];

        // Test public API routes
        foreach ($publicApiRoutes as $route) {
            try {
                $response = $this->get($route);
                
                $results['public'][$route] = [
                    'status' => $response->getStatusCode(),
                    'success' => $response->getStatusCode() < 400,
                    'json' => $response->getStatusCode() === 200 ? true : false,
                    'error' => $response->getStatusCode() >= 400 ? 'HTTP ' . $response->getStatusCode() : null
                ];
            } catch (\Exception $e) {
                $results['public'][$route] = [
                    'status' => 500,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Test protected API routes with admin user
        $admin = $this->testUsers['admin'];
        foreach ($protectedApiRoutes as $route) {
            try {
                $response = $this->actingAs($admin)->get($route, ['Accept' => 'application/json']);
                
                $results['protected'][$route] = [
                    'status' => $response->getStatusCode(),
                    'success' => $response->getStatusCode() < 400,
                    'json' => $response->getStatusCode() === 200 ? true : false,
                    'error' => $response->getStatusCode() >= 400 ? 'HTTP ' . $response->getStatusCode() : null
                ];
            } catch (\Exception $e) {
                $results['protected'][$route] = [
                    'status' => 500,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        $this->crawlResults['api'] = $results;
        Log::info('API routes crawl completed', ['results' => $results]);
        
        // Assert API health endpoint works
        $this->assertTrue($results['public']['/api/v1/health']['success'], 'API health check failed');
    }

    /**
     * Test authentication flows
     */
    public function test_authentication_flows()
    {
        $results = [];

        // Test login flow
        try {
            $response = $this->post('/login', [
                'email' => 'student@zenithalms.com',
                'password' => 'password'
            ]);
            
            $results['login'] = [
                'status' => $response->getStatusCode(),
                'success' => $response->getStatusCode() === 302, // Should redirect after login
                'redirect' => $response->getStatusCode() === 302 ? $response->headers->get('Location') : null
            ];
        } catch (\Exception $e) {
            $results['login'] = [
                'status' => 500,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        // Test logout flow
        try {
            $user = $this->testUsers['student'];
            $response = $this->actingAs($user)->post('/logout');
            
            $results['logout'] = [
                'status' => $response->getStatusCode(),
                'success' => $response->getStatusCode() === 302, // Should redirect after logout
                'redirect' => $response->getStatusCode() === 302 ? $response->headers->get('Location') : null
            ];
        } catch (\Exception $e) {
            $results['logout'] = [
                'status' => 500,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        $this->crawlResults['auth'] = $results;
        Log::info('Authentication flows crawl completed', ['results' => $results]);
        
        // Assert login and logout work
        $this->assertTrue($results['login']['success'], 'Login flow failed');
        $this->assertTrue($results['logout']['success'], 'Logout flow failed');
    }

    /**
     * Generate crawl report
     */
    public function test_generate_crawl_report()
    {
        // Run all crawl tests
        $this->test_public_routes_crawl();
        $this->test_authenticated_routes_crawl();
        $this->test_api_routes_crawl();
        $this->test_authentication_flows();

        // Generate summary report
        $report = [
            'timestamp' => now()->toISOString(),
            'summary' => [
                'total_routes_tested' => 0,
                'successful_routes' => 0,
                'failed_routes' => 0,
                'critical_failures' => 0
            ],
            'details' => $this->crawlResults
        ];

        // Calculate summary statistics
        foreach ($this->crawlResults as $category => $routes) {
            if (is_array($routes)) {
                foreach ($routes as $routeGroup) {
                    if (is_array($routeGroup)) {
                        foreach ($routeGroup as $result) {
                            $report['summary']['total_routes_tested']++;
                            if ($result['success']) {
                                $report['summary']['successful_routes']++;
                            } else {
                                $report['summary']['failed_routes']++;
                                if ($result['status'] >= 500) {
                                    $report['summary']['critical_failures']++;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Save report to file
        $reportPath = storage_path('app/reports/backend_crawl_' . now()->format('Y-m-d_H-i-s') . '.json');
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));

        Log::info('Backend crawl report generated', ['report_path' => $reportPath, 'summary' => $report['summary']]);
        
        // Assert no critical failures
        $this->assertEquals(0, $report['summary']['critical_failures'], 
            'Critical failures detected: ' . $report['summary']['critical_failures']);
    }
}
