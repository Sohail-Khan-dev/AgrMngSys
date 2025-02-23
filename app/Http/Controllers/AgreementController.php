<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Agreement;
use App\Models\SignStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AgreementController extends Controller
{
    public function createAgreement(Request $request) {
         // Validation rules
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email',
        'title' => 'required|string|max:255',
        'agreement_file' => 'required|file|mimes:pdf,doc,docx|max:2048',
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
        $agreement->agreement_file = $request->agreement_file;
        if($request->signature){
            $sign_status = new SignStatus();
            $sign_status->user_id = $user->id;
            $sign_status->agreement_id = $agreement->id;
            $sign_status->signature = $request->signature;
            $sign_status->save();
        }
        $agreement->save();
        return response()->json(['message' => 'Agreement created successfully'], 200);
    }

}
