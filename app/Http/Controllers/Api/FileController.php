<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function studentIdFile(StudentProfile $studentProfile): StreamedResponse
    {
        $this->authorize('viewIdFile', $studentProfile);

        abort_unless($studentProfile->student_id_file_path, 404);

        return Storage::disk('local')->response($studentProfile->student_id_file_path);
    }

    public function claimPhoto(Claim $claim): StreamedResponse
    {
        $this->authorize('viewPhoto', $claim);

        abort_unless($claim->photo_path, 404);

        return Storage::disk('local')->response($claim->photo_path);
    }
}
