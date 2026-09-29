<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Survey;
use App\Models\SurveyAssignment;
use App\Models\SurveyVariable;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileMitraFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_mitra_can_login_with_sanctum_token(): void
    {
        $this->seed();

        $response = $this->postJson('/api/mobile/auth/login', [
            'email' => 'mitra@bps.go.id',
            'password' => 'password123',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_entry_rejects_invalid_number_variable_format(): void
    {
        Storage::fake('public');
        $this->seed();

        $mitra = User::query()->where('email', 'mitra@bps.go.id')->firstOrFail();
        $pegawai = User::query()->where('email', 'pegawai@bps.go.id')->firstOrFail();

        $team = Team::query()->create(['name' => 'Team A']);
        $team->users()->attach($pegawai->id);

        $district = District::query()->firstOrCreate(['name' => 'Kepulauan Seribu Utara']);

        $survey = Survey::query()->create([
            'team_id' => $team->id,
            'created_by' => $pegawai->id,
            'title' => 'Survei Uji',
            'description' => 'Uji',
            'total_target' => 5,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'status' => 'Berjalan',
        ]);

        $variable = SurveyVariable::query()->create([
            'survey_id' => $survey->id,
            'name' => 'Umur',
            'data_type' => 'number',
            'example_format' => '35',
        ]);

        SurveyAssignment::query()->create([
            'survey_id' => $survey->id,
            'mitra_id' => $mitra->id,
            'target' => 2,
            'current_progress' => 0,
        ]);

        $token = $mitra->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/mobile/surveys/'.$survey->id.'/entries', [
                'respondent_name' => 'Responden A',
                'district_id' => $district->id,
                'evidence_photo' => UploadedFile::fake()->image('proof.jpg'),
                'variables' => [
                    [
                        'survey_variable_id' => $variable->id,
                        'value' => 'abc',
                    ],
                ],
            ]);

        $response->assertStatus(422);
    }
}
