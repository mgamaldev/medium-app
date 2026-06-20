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

        $follower->follow($user);

        $user->notify(new NewFollowerNotification($follower));

        return response()->json($user, 200);

    }
}
