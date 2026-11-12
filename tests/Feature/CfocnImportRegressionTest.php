<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CfocnImportRegressionTest extends TestCase
{
    /**
     * Regression check for the infrastructure-filtered CFOCN matcher
     * (POST /api/cfocn-filter/run, CFOCN exact rule): the known test
     * fixtures must keep producing these exact unique-job-number counts.
     * If this fails, the matching logic changed — do not "fix" it here,
     * report the difference and stop.
     */
    public function test_cfocn_import_produces_expected_counts(): void
    {
        $builderPath = base_path('testfiles/builder_cfocn_updated.xlsx');
        $cfoPath = base_path('testfiles/old_cfocn_test.xlsx');

        $this->assertFileExists($builderPath, 'Missing fixture: testfiles/builder_cfocn_updated.xlsx');
        $this->assertFileExists($cfoPath, 'Missing fixture: testfiles/old_cfocn_test.xlsx');

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->post('/api/cfocn-filter/run', [
            'builder' => new UploadedFile($builderPath, 'builder_cfocn_updated.xlsx', null, null, true),
            'cfo' => new UploadedFile($cfoPath, 'old_cfocn_test.xlsx', null, null, true),
            'all_infrastructures' => '0',
            'infrastructures' => ['CFOCN'],
            'rule' => 'exact',
        ]);

        $response->assertOk();

        // Headline counts are unique CFOCN job numbers, not raw row counts.
        // No Bill expectation here until the manager confirms unique vs row
        // count for that figure.
        $response->assertJsonPath('summary.total_cfocn_work_orders', 3881);
        $response->assertJsonPath('summary.matched', 2947);
        $response->assertJsonPath('summary.not_matched', 934);
        $response->assertJsonPath('summary.matched_plus_not_matched_equals_total', true);

        // The Match / Not match sheets are present in the response.
        $this->assertIsArray($response->json('match'));
        $this->assertIsArray($response->json('not_match'));
    }
}
