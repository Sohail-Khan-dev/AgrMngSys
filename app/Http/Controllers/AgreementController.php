<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\SignStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AgreementController extends Controller
{
    public function createAgreement(Request $request)
    {
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

        // Get agreements with base query
        $agreements = Agreement::where('user_id', $user->id)
            ->select('id', 'title', 'created_at')
            ->get();

        // Format the dates and prepare the response data
        $formattedAgreements = $agreements->map(function ($agreement) {
            return [
                'id' => $agreement->id,
                'title' => $agreement->title,
                'created_at' => $agreement->created_at->format('Y-m-d')
            ];
        });

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
            'agreements' => $formattedAgreements
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

        // Format the agreement data with formatted dates
        $formattedAgreement = [
            'id' => $agreement->id,
            'user_id' => $agreement->user_id,
            'title' => $agreement->title,
            'slug' => $agreement->slug,
            'agreement_file' => $agreement->agreement_file,
            'sign_status_id' => $agreement->sign_status_id,
            'created_at' => $agreement->created_at->format('Y-m-d'),
            'updated_at' => $agreement->updated_at->format('Y-m-d')
        ];

        return response()->json([
            'status' => true,
            'message' => 'Agreement retrieved successfully',
            'agreement' => $formattedAgreement
        ], 200);
    }

    /**
     * Share an agreement with multiple users
     */
    public function shareAgreement(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'agreement_id' => 'required|exists:agreements,id',
            // 'emails' => 'required|array',
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

        $agreement = Agreement::findOrFail($request->agreement_id);
        $sharedWith = "None";
        $alreadyShared = "None";
        $errors = "None";
        $email = $request->email;
        // Process each email
        // foreach ($request->emails as $email) {
            $user = User::where('email', $email)->first();

            if (!$user) {
                $errors = "User with email {$email} not found";
            }

            // Check if the agreement is already shared with this user
            $existingShare = SignStatus::where('agreement_id', $agreement->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingShare) {
                $alreadyShared = $email;
            }

            // Create a new sign status record for this user
            $signStatus = new SignStatus();
            $signStatus->user_id = $user->id;
            $signStatus->agreement_id = $agreement->id;
            $signStatus->status = 'pending';
            $signStatus->save();

            $sharedWith = $email;
        // }

        return response()->json([
            'status' => true,
            'message' => 'Agreement shared successfully',
            'shared_with' => $sharedWith,
            'already_shared' => $alreadyShared,
            'errors' => $errors
        ], 200);
    }

    /**
     * Get all users who have access to a specific agreement
     */
    public function getAgreementUsers(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'agreement_id' => 'required|exists:agreements,id',
        ]);

        // If validation fails, return response with errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $agreement = Agreement::findOrFail($request->agreement_id);

        // Get all sign statuses for this agreement
        $signStatuses = SignStatus::where('agreement_id', $agreement->id)->get();

        if ($signStatuses->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No users have access to this agreement'
            ], 404);
        }

        // Get user details for each sign status
        $users = [];
        foreach ($signStatuses as $signStatus) {
            $user = User::find($signStatus->user_id);
            if ($user) {
                $users[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $signStatus->status,
                    'signature' => $signStatus->signature,
                    'shared_at' => $signStatus->created_at->format('Y-m-d')
                ];
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Agreement users retrieved successfully',
            'agreement' => [
                'id' => $agreement->id,
                'title' => $agreement->title,
                'created_at' => $agreement->created_at->format('Y-m-d')
            ],
            'users' => $users
        ], 200);
    }
}
