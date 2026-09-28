<?php

namespace App\Http\Controllers\Api;

use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Institution;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ChallengeIdGenerator;
use App\Services\PhoneNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly PhoneNormalizer $phoneNormalizer,
        private readonly ChallengeIdGenerator $challengeIdGenerator,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $institution = Institution::findOrFail($data['institution_id']);

        [$user, $profile] = DB::transaction(function () use ($data, $institution, $request) {
            $user = User::create([
                'name' => $data['full_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => UserRole::Student,
            ]);

            $filePath = $request->file('student_id_file')->store('student-ids', 'local');

            $profile = StudentProfile::create([
                'user_id' => $user->id,
                'institution_id' => $institution->id,
                'phone' => $this->phoneNormalizer->normalize($data['phone']),
                'state' => $data['state'],
                'student_id_number' => $data['student_id_number'],
                'department' => $data['department'],
                'graduation_year' => $data['graduation_year'],
                'status' => StudentStatus::Approved,
                'approved_at' => now(),
            ]);
            $profile->student_id_file_path = $filePath;
            $profile->challenge_id = $this->challengeIdGenerator->generateFor($institution);
            $profile->save();

            return [$user, $profile];
        });

        $this->activityLogger->record('student.registered', $user, ['challenge_id' => $profile->challenge_id], $user);

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('studentProfile')),
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('studentProfile')),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(status: 204);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()->load('studentProfile')),
        ]);
    }
}
