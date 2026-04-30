<?php

use App\Mail\ApplicationStatusUpdatedMail;
use App\Models\Application;
use App\Models\Company;
use App\Models\Internship;
use App\Models\InternshipProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function createCompanyAccount(array $companyAttributes = [], array $userAttributes = []): array
{
    $user = User::factory()->create(array_merge([
        'role' => User::ROLE_COMPANY,
        'is_active' => true,
    ], $userAttributes));

    $company = Company::create(array_merge([
        'user_id' => $user->id,
        'name' => 'PT InternHub Test',
        'address' => 'Jl. Marketplace No. 1',
        'email' => 'company'.uniqid().'@example.test',
        'is_verified' => true,
        'verified_at' => now(),
        'plan_type' => 'free',
    ], $companyAttributes));

    return [$user, $company];
}

function createOpenProgram(Company $company, array $attributes = []): InternshipProgram
{
    return InternshipProgram::create(array_merge([
        'company_id' => $company->id,
        'title' => 'Backend Intern',
        'description' => str_repeat('Belajar membangun aplikasi Laravel. ', 3),
        'requirements' => 'Dasar PHP dan database.',
        'quota' => 2,
        'field' => 'IT',
        'start_date' => now()->addDays(10)->toDateString(),
        'end_date' => now()->addDays(40)->toDateString(),
        'registration_start' => now()->subDay()->toDateString(),
        'registration_end' => now()->addDays(5)->toDateString(),
        'status' => 'open',
    ], $attributes));
}

it('blocks users from accessing another role area', function () {
    $intern = User::factory()->create([
        'role' => User::ROLE_INTERN,
        'is_active' => true,
    ]);

    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($intern)
        ->get(route('company.programs.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('intern.applications.index'))
        ->assertForbidden();
});

it('blocks unverified companies from managing job posts', function () {
    [$companyUser] = createCompanyAccount([
        'is_verified' => false,
        'verified_at' => null,
    ]);

    $this->actingAs($companyUser)
        ->get(route('company.programs.index'))
        ->assertForbidden();
});

it('limits free interns to two applications', function () {
    [, $company] = createCompanyAccount();
    $intern = User::factory()->create([
        'role' => User::ROLE_INTERN,
        'is_active' => true,
        'plan_type' => 'free',
    ]);

    $programs = collect(range(1, 3))
        ->map(fn (int $index) => createOpenProgram($company, ['title' => "Program {$index}"]));

    Application::create([
        'user_id' => $intern->id,
        'internship_program_id' => $programs[0]->id,
        'motivation_letter' => str_repeat('Saya ingin belajar dan bertumbuh. ', 4),
        'status' => Application::STATUS_PENDING,
        'applied_at' => now(),
    ]);

    Application::create([
        'user_id' => $intern->id,
        'internship_program_id' => $programs[1]->id,
        'motivation_letter' => str_repeat('Saya ingin belajar dan bertumbuh. ', 4),
        'status' => Application::STATUS_REJECTED,
        'applied_at' => now(),
    ]);

    $this->actingAs($intern)
        ->post(route('intern.applications.store'), [
            'internship_program_id' => $programs[2]->id,
            'motivation_letter' => str_repeat('Motivasi saya mengikuti program ini sangat kuat. ', 4),
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Application::where('user_id', $intern->id)->count())->toBe(2);
});

it('orders premium applicants before free applicants', function () {
    [, $company] = createCompanyAccount();
    $program = createOpenProgram($company);

    $freeUser = User::factory()->create([
        'role' => User::ROLE_INTERN,
        'is_active' => true,
        'plan_type' => 'free',
    ]);

    $premiumUser = User::factory()->create([
        'role' => User::ROLE_INTERN,
        'is_active' => true,
        'plan_type' => 'premium',
        'premium_until' => now()->addDays(10),
    ]);

    Application::create([
        'user_id' => $freeUser->id,
        'internship_program_id' => $program->id,
        'motivation_letter' => 'Free applicant',
        'status' => Application::STATUS_PENDING,
        'applied_at' => now()->addMinute(),
    ]);

    Application::create([
        'user_id' => $premiumUser->id,
        'internship_program_id' => $program->id,
        'motivation_letter' => 'Premium applicant',
        'status' => Application::STATUS_PENDING,
        'applied_at' => now(),
    ]);

    expect(Application::query()->priorityOrder()->first()->user_id)
        ->toBe($premiumUser->id);
});

it('lets a verified company accept an application and creates an internship', function () {
    Mail::fake();

    [$companyUser, $company] = createCompanyAccount();
    $program = createOpenProgram($company);
    $intern = User::factory()->create([
        'role' => User::ROLE_INTERN,
        'is_active' => true,
    ]);

    $application = Application::create([
        'user_id' => $intern->id,
        'internship_program_id' => $program->id,
        'motivation_letter' => str_repeat('Saya siap belajar dan berkontribusi. ', 4),
        'status' => Application::STATUS_PENDING,
        'applied_at' => now(),
    ]);

    $this->actingAs($companyUser)
        ->post(route('company.applications.accept', $application), [
            'notes' => 'Selamat bergabung di program magang kami.',
        ])
        ->assertRedirect(route('company.applications.show', $application));

    expect($application->fresh()->status)->toBe(Application::STATUS_ACCEPTED);
    expect(Internship::where('application_id', $application->id)->exists())->toBeTrue();

    Mail::assertQueued(ApplicationStatusUpdatedMail::class);
});
