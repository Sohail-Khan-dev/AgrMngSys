<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Agreement;
use App\Models\SignStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AgreementController extends Controller
{
    public function createAgreement(Request $request)
    {
        // dd($request->all());
        // Validation rules
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'slug' => 'required|string',
            'title' => 'required|string|max:255',
            'agreement_file' => 'required|string',
            'signature' => 'nullable|string',
        ]);

        // If validation fails, return response with errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Fetch user ID
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }
        $agreement = new Agreement();
        $agreement->user_id = $user->id;
        $agreement->title = $request->title;
        $agreement->slug = $request->slug;
        $agreement->agreement_file = $request->agreement_file;
        $agreement->save();
        if ($request->signature) {
            $sign_status = new SignStatus();
            $sign_status->user_id = $user->id;
            $sign_status->agreement_id = $agreement->id;
            $sign_status->signature = $request->signature;
            $sign_status->save();
        }

        return response()->json(['message' => 'Agreement created successfully'], 200);
    }
    public function getAgreements(Request $request)
    {
        // Validate email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        // If validation fails, return response with errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get user ID
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Get agreements
        $agreements = Agreement::where('user_id', $user->id)->select('id', 'title', 'created_at')->get();

        // Check if agreements exist
        if ($agreements->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No agreements found for this user'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Agreements retrieved successfully',
            'agreements' => $agreements
        ], 200);
    }
    public function getSigleAgreement(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:agreements,id',
        ]);

        // If validation fails, return response with errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $agreement = Agreement::where('id', $request->id)->first();

        if (!$agreement) {
            return response()->json([
                'status' => false,
                'message' => 'Agreement not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Agreement retrieved successfully',
            'agreement' => $agreement
        ], 200);
    }
}
