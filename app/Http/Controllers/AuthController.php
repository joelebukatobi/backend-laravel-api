<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed|min:8',
            'sources' => 'nullable|string',
            'categories' => 'nullable|string',
        ]);

        $user = User::create([
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'sources' => $this->parseCSV($request->input('sources')),
            'categories' => $this->parseCSV($request->input('categories')),
        ]);

        return response()->json(['message' => 'User registered successfully'], 201);
    }

    /**
     * Parse the comma-separated values into an array.
     *
     * @param string|null $value
     * @return array|null
     */
    private function parseCSV(?string $value)
    {
        if ($value === null) {
            return null;
        }

        return array_map('trim', explode(',', $value));
    }


    /**
     * Authenticate a user and generate an access token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json(['access_token' => $token], 200);
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    /**
     * Get the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }

    /**
     * Logout the authenticated user (revoke the access token).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'User logged out successfully']);
    }
}
