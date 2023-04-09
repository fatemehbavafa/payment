<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8']
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            User::create([
                'email'    => $request->email,
                'password' => Hash::make($request->password)
            ]);
        }

        if (!Auth::attempt([
            'email'    => $request->email,
            'password' => $request->password,
        ]))
            return response()->json([
                'status' => '401',
                'result' => [
                    'message' => 'ایمیل یا رمز عبور نادرست است',
                    'lang'    => 'en'
                ]
            ]);

        $user        = Auth::user();
        $accessToken = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => '200',
            'result' => [
                'token' => $accessToken,
            ]
        ]);

    }

    public function status($package_id): JsonResponse
    {
        $userGetPackage = Order::where('user_id', Auth::id())
            ->where('package_id', $package_id)
            ->where('paid', true)
            ->first();

        return response()->json([
            'status' => '200',
            'result' => [
                'token' => !($userGetPackage == null)
            ]
        ]);
    }
}
