<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\NewFollowerNotification;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function store(User $user)
    {
        /** @var User $follower */
        $follower = Auth::user();

        if ($follower->id === $user->id) {
            return response()->json(['message' => 'You cannot follow yourself'], 422);
        }

        $follower->follow($user);

        $user->notify(new NewFollowerNotification($user, $follower));

        return response()->json($user, 200);
    }
}
