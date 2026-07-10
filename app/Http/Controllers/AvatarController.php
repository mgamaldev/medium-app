<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\S3StorageService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AvatarController extends Controller
{
    public function getAvatarPreSignedUrl(Request $request, S3StorageService $s3StorageService): JsonResponse
    {
        $validatedData = $request->validate(
            [
                'file_name' => ['required', 'string'],
                'content_type' => ['required', 'string'],
            ]
        );

        $result = $s3StorageService->generatePresignedUrl(
            'avatars',
            1024000,
            $validatedData['file_name'],
            $validatedData['content_type']
        );

        return response()->json($result);
    }

    public function confirmAvatar(Request $request)
    {
        $validatedData = $request->validate(
            [
                'file_key' => ['required', 'string', 'startsWith:avatars/'],
            ]
        );

        $fileKey = $validatedData['file_key'];
        /** @var User $user */
        $user = Auth::user();

        $oldAvatar = $user->avatar;

        if ($oldAvatar) {
            Storage::disk('s3')->delete($oldAvatar);
        }

        $user->update([
            'avatar' => $fileKey,
        ]);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('s3');

        return response()->json([
            'message' => 'Avatar updated successfully',
            'avatar' => $disk->url($user->avatar),
        ]);
    }
}
