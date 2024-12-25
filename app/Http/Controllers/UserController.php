<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string',
            'dob' => 'nullable|date',
            'id_number' => 'nullable|string',
            'password' => 'required|string|min:6',
        ]);
        // If validation fails, return a 422 response with errors
        if ($validator->fails()) {
            return response()->json(['status' => 422, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'dob' => $request->dob,
                'id_number' => $request->id_number,
                'password' => Hash::make($request->password),
            ]);
            return response()->json(['status' => 200, 'message' => 'User registered successfully', 'user' => $user], 200);
        }catch (\Exception $e) {
            // Return a 500 response if there's a server error
            return response()->json(['status' => 500, 'message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
    }
    public function login(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'message' => 'Validation errors', 'errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');
        try {
            if (auth()->attempt($credentials)) {
                $user = auth()->user();
                $token = $user->createToken('authToken')->plainTextToken;

                return response()->json([
                    'status' => 200,
                    'message' => 'Login successful',
                    'user' => $user, 'token' => $token]);
            }
            return response()->json(['status'=>401 ,'message' => 'Invalid credentials theek da credentials'], 401);
        }catch (\Exception $e)
        {
            return response()->json(['status' => 500, 'message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
    }
    public function verifyOTP(Request $request){
        return response()->json(['status' => 200, 'message' => 'Successfully verified']);
    }

    public function fetchAllUsers(){
        $users = User::all();
        return response()->json([
           'users' => $users
        ]);

    }
}
