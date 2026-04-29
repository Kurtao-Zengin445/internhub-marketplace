<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Intern;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    private const ROLES = ['intern', 'supervisor', 'company'];
    private const PENDING_GOOGLE_REGISTRATION = 'pending_google_registration';

    public function create(Request $request): View
    {
        return view('auth.register', [
            'selectedRole' => old('role', $this->selectedRole($request)),
            'roleMeta' => $this->roleMeta(),
        ]);
    }

    public function completeProfile(): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user && in_array($user->role, self::ROLES, true)) {
            if ($this->hasCompletedProfile($user)) {
                return redirect()->route('dashboard');
            }

            return view('auth.complete-profile', [
                'user' => $user,
                'roleMeta' => $this->roleMeta(),
            ]);
        }

        $pendingRegistration = request()->session()->get(self::PENDING_GOOGLE_REGISTRATION);

        if (!$pendingRegistration || !in_array($pendingRegistration['role'] ?? null, self::ROLES, true)) {
            return redirect()->route('register');
        }

        return view('auth.complete-profile', [
            'user' => User::make([
                'name' => $pendingRegistration['name'],
                'email' => $pendingRegistration['email'],
                'role' => $pendingRegistration['role'],
                'avatar' => $pendingRegistration['avatar'] ?? null,
            ]),
            'roleMeta' => $this->roleMeta(),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $role = $this->selectedRole($request);
        $validated = $this->validateRegistration($request, $role);

        $user = DB::transaction(function () use ($validated, $role) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $role,
                'is_active' => true,
            ]);

            $this->createProfileForRole($user, $validated, $role);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    public function storeCompleteProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user && in_array($user->role, self::ROLES, true)) {
            if ($this->hasCompletedProfile($user)) {
                return redirect()->route('dashboard');
            }

            $validated = $this->validateProfileForCompletion($request, $user);

            DB::transaction(function () use ($user, $validated) {
                $user->forceFill([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();

                $this->createProfileForRole($user, $validated, $user->role);
            });

            return redirect()
                ->route('dashboard')
                ->with('status', 'Profil berhasil dilengkapi.');
        }

        $pendingRegistration = $request->session()->get(self::PENDING_GOOGLE_REGISTRATION);

        abort_unless($pendingRegistration && in_array($pendingRegistration['role'] ?? null, self::ROLES, true), 403);

        $role = $pendingRegistration['role'];
        $validated = $this->validateRegistrationForRole($request, $role, false);

        $user = DB::transaction(function () use ($pendingRegistration, $validated, $role) {
            $this->deleteMatchingPendingGoogleUsers(
                $pendingRegistration['google_id'] ?? null,
                $pendingRegistration['email'] ?? null
            );

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make(str()->random(40)),
                'role' => $role,
                'is_active' => true,
                'google_id' => $pendingRegistration['google_id'] ?? null,
                'avatar' => $pendingRegistration['avatar'] ?? null,
                'email_verified_at' => now(),
            ]);

            $this->createProfileForRole($user, $validated, $role);

            return $user;
        });

        $request->session()->forget(self::PENDING_GOOGLE_REGISTRATION);
        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Profil berhasil dilengkapi.');
    }

    public function cancelCompleteProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user && in_array($user->role, self::ROLES, true) && !$this->hasCompletedProfile($user)) {
            DB::transaction(function () use ($user) {
                $user->notifications()->delete();
                $user->delete();
            });

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('register')
                ->with('status', 'Pendaftaran Google dibatalkan. Silakan daftar atau login dengan akun lain.');
        }

        $pendingRegistration = $request->session()->get(self::PENDING_GOOGLE_REGISTRATION);

        abort_unless($pendingRegistration && in_array($pendingRegistration['role'] ?? null, self::ROLES, true), 403);

        DB::transaction(function () use ($pendingRegistration) {
            $this->deleteMatchingPendingGoogleUsers(
                $pendingRegistration['google_id'] ?? null,
                $pendingRegistration['email'] ?? null
            );
        });

        $request->session()->forget(self::PENDING_GOOGLE_REGISTRATION);

        return redirect()
            ->route('register')
            ->with('status', 'Pendaftaran Google dibatalkan. Silakan daftar atau login dengan akun lain.');
    }

    private function selectedRole(Request $request): string
    {
        $role = $request->input('role', 'intern');

        return in_array($role, self::ROLES, true) ? $role : 'intern';
    }

    private function validateRegistration(Request $request, string $role): array
    {
        return $this->validateRegistrationForRole($request, $role, true);
    }

    private function validateRegistrationForRole(Request $request, string $role, bool $withPassword = true): array
    {
        return match ($role) {
            'intern' => $this->validateInternProfile($request, $withPassword),
            'supervisor' => $this->validateSupervisorProfile($request, $withPassword),
            'company' => $this->validateCompanyProfile($request, $withPassword),
        };
    }

    private function validateProfileForCompletion(Request $request, User $user): array
    {
        return match ($user->role) {
            'intern' => $this->validateInternProfile($request, false, $user),
            'supervisor' => $this->validateSupervisorProfile($request, false, $user),
            'company' => $this->validateCompanyProfile($request, false, $user),
        };
    }

    private function validateInternProfile(Request $request, bool $withPassword = false, ?User $user = null): array
    {
        $rules = [
            'role' => ['required', Rule::in(self::ROLES)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'headline' => ['nullable', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'class' => ['nullable', 'string', 'max:255'],
            'nis' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'in:male,female'],
            'birth_date' => ['nullable', 'date'],
        ];

        if ($user?->exists) {
            $rules['email'] = [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ];
        }

        if ($withPassword) {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }

        return $request->validate($rules);
    }

    private function validateSupervisorProfile(Request $request, bool $withPassword = true, ?User $user = null): array
    {
        $rules = [
            'role' => ['required', Rule::in(self::ROLES)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'nip' => ['nullable', 'string', 'max:30', 'unique:supervisors,nip'],
            'position' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];

        if ($user?->exists) {
            $rules['email'] = ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)];
        }

        if ($withPassword) {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }

        return $request->validate($rules);
    }

    private function validateCompanyProfile(Request $request, bool $withPassword = true, ?User $user = null): array
    {
        $rules = [
            'role' => ['required', Rule::in(self::ROLES)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'company_email' => ['nullable', 'email'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_person_phone' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'industry' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url'],
        ];

        if ($user?->exists) {
            $rules['email'] = ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)];
        }

        if ($withPassword) {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }

        return $request->validate($rules);
    }

    private function createProfileForRole(User $user, array $validated, string $role): void
    {
        match ($role) {
            'intern' => Intern::create([
                'user_id' => $user->id,
                'nim' => $validated['nis'] ?? null,
                'education_level' => $validated['class'] ?? null,
                'major' => $validated['major'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'date_of_birth' => $validated['birth_date'] ?? null,
                'gender' => $validated['gender'] ?? null,
            ]),
            'supervisor' => Supervisor::create([
                'user_id' => $user->id,
                'nip' => $validated['nip'] ?? null,
                'position' => $validated['position'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]),
            'company' => Company::create([
                'user_id' => $user->id,
                'name' => $validated['company_name'],
                'address' => $validated['address'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['company_email'] ?? $validated['email'],
                'contact_person' => $validated['contact_person'] ?? null,
                'contact_person_phone' => $validated['contact_person_phone'] ?? null,
                'description' => $validated['description'] ?? null,
                'industry' => $validated['industry'] ?? null,
                'website' => $validated['website'] ?? null,
            ]),
        };

        if (!empty($validated['headline'])) {
            $user->update(['headline' => $validated['headline']]);
        }
    }

    private function hasCompletedProfile(User $user): bool
    {
        return $user->hasCompletedRoleProfile();
    }

    private function deleteMatchingPendingGoogleUsers(?string $googleId, ?string $email): void
    {
        if (!$googleId && !$email) {
            return;
        }

        User::query()
            ->when($googleId, fn ($query) => $query->where('google_id', $googleId))
            ->when(
                $email,
                fn ($query) => $query->when(
                    $googleId,
                    fn ($nestedQuery) => $nestedQuery->orWhere('email', $email),
                    fn ($nestedQuery) => $nestedQuery->where('email', $email)
                )
            )
            ->get()
            ->filter(fn (User $candidate) => $candidate->hasPendingGoogleRegistration())
            ->each(function (User $candidate): void {
                $candidate->notifications()->delete();
                $candidate->delete();
            });
    }

    private function roleMeta(): array
    {
        return [
            'intern' => [
                'label' => 'Intern',
                'description' => 'Buat profil intern, jelajahi lowongan magang, dan lamar company secara langsung.',
                'icon' => 'bi-mortarboard-fill',
                'accent' => 'blue',
            ],
            'supervisor' => [
                'label' => 'Pembimbing',
                'description' => 'Pantau peserta, verifikasi laporan, dan isi evaluasi akhir magang.',
                'icon' => 'bi-person-workspace',
                'accent' => 'amber',
            ],
            'company' => [
                'label' => 'Perusahaan',
                'description' => 'Buka program magang, seleksi pelamar, dan kelola proses magang dari satu dashboard.',
                'icon' => 'bi-briefcase-fill',
                'accent' => 'violet',
            ],
        ];
    }
}
