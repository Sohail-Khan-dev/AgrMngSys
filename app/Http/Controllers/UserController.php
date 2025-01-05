<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Mail\OTPMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
            $this->sendOtpEmail($user);
            return response()->json(['status' => 200, 'message' => 'User registered successfully', 'user' => $user], 200);
        }catch (\Exception $e) {
            // Return a 500 response if there's a server error
            return response()->json(['status' => 500, 'message' => 'Internal Server Error', 'error' => $e->getMessage()], 500);
        }
    }
    public function sendOtpEmail(User $user){
        // dd($user);
        $otp = rand(100000,999999);
        try {
            // Start a database transaction
            DB::beginTransaction();
    
            // Save the OTP to the user's record
            $user->otp = $otp;
            $user->otp_expires_at = now()->addMinutes(10); 
            $user->save();
    
            // Send the OTP email
            Mail::to($user->email)->send(new OTPMail($otp));
    
            // Commit the transaction if email is sent successfully
            DB::commit();
    
            return response()->json(['msg' => 'OTP sent successfully']);
        } catch (\Exception $e) {
            // Rollback the transaction if something goes wrong
            DB::rollBack();
    
            // Log the error for debugging
            \Log::error('Error sending OTP email: ' . $e->getMessage());
    
            return response()->json(['error' => 'Failed to send OTP. Please try again.'], 500);
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
    
    // public function verifyOTP(Request $request){
    //     return response()->json(['status' => 200, 'message' => 'Successfully verified']);
    // }


    public function  fetchAllUsers(){
        $users = User::all();
        return response()->json([
           'users' => $users
        ]);

    }
    public function verifyOtp(Request $request)
    {
        // Validate the request
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);
        // Retrieve the user by email
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }
        // Check if OTP matches
        if ($user->otp !== $request->otp) {
            return response()->json(['error' => 'Invalid OTP.'], 400);
        }
        // Optional: Check if OTP has expired (if you store expiration time)
        if (!empty($user->otp_expires_at) && Carbon::now()->greaterThan($user->otp_expires_at)) {   // In near Future we will do this
            return response()->json(['error' => 'OTP has expired.'], 400);
        }

        // If OTP is valid, you can mark the user as verified or perform any action
        $user->otp = null; // Clear OTP after verification
        $user->save();
        return response()->json(['msg' => 'OTP verified successfully.'], 200);
    }
    public function ResendOtp(Request $request){
        $request->validate([
            'email' => 'required|email',
        ]);
    
        // Retrieve the user by email
        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }
        $this->sendOtpEmail($user);
    }
}
