<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\SignStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AgreementController extends Controller
{
    public function createAgreement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'slug' => 'required|string',
            'title' => 'required|string|max:255',
            'agreement_file' => 'required|string',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'id' => 'nullable|exists:agreements,id|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }
        if($request->id !== 0 && $request->id !== null){
            $agreement = Agreement::find($request->id);
        }else{
            $agreement = new Agreement();
        }
        $agreement->user_id = $user->id;
        $agreement->title = $request->title;
        $agreement->slug = $request->slug;
        $agreement->agreement_file = $request->agreement_file;
        $agreement->save();
        // Get all sign statuses for this agreement
        $sign_statuses = SignStatus::where('agreement_id', $agreement->id)->get();

        // If no sign statuses exist, create a default one
        if ($sign_statuses->isEmpty()) {
            $signaturePath = null;

            // Handle signature image upload
            if ($request->hasFile('signature')) {
                $signatureFile = $request->file('signature');
                $fileName = 'signature_' . $user->id . '_' . $agreement->id . '_' . time() . '.' . $signatureFile->getClientOriginalExtension();
                $signaturePath = $signatureFile->storeAs('signatures', $fileName, 'public');
            }

            $sign_status = new SignStatus();
            $sign_status->user_id = $user->id;
            $sign_status->agreement_id = $agreement->id;
            $sign_status->signature = $signaturePath ?? 'none';
            $sign_status->status = 'draft';
            $sign_status->save();
        }
        return response()->json(['agreement_id'=>$agreement->id, 'message' => 'Agreement created successfully'], 200);
    }
    public function getAgreements(Request $request)
    {
        $status = $request->status;
        if(!$status){
            $status = 'draft';
        }
        // Validate email
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'status' => 'required|string',
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

        // Get agreements owned by the user
        // i wnant to get the agreeements that signStaus is draft
        $ownedAgreements = Agreement::where('user_id', $user->id)
            ->with('signStatus')
            ->whereHas('signStatus', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->select('id', 'title', 'created_at')
            ->get();

        // Get agreements shared with the user
        $sharedAgreementIds = SignStatus::with('user')->where('user_id', $user->id)->where('status', $status)
            ->pluck('agreement_id')
            ->toArray();

        $sharedAgreements = Agreement::whereIn('id', $sharedAgreementIds)
            ->select('id', 'title', 'created_at')
            ->get();

        // Merge both collections
        $agreements = $ownedAgreements->merge($sharedAgreements);
        // Format the dates and prepare the response data
        $formattedAgreements = $agreements->map(function ($agreement) use ($user) {
            $shared_with = SignStatus::with('user')->where('agreement_id', $agreement->id)
                ->where('user_id', '!=', $user->id)  // Get all users except the current user
                ->first();
            return [
                'id' => $agreement->id,
                'title' => $agreement->title,
                'created_at' => $agreement->created_at->format('Y-m-d'),
                'is_owner' => $agreement->user_id == $user->id,
                'shared_with' => $shared_with ? $shared_with->user->name : null

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
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $agreement = Agreement::with(['signStatus' => function($query) use ($request) {
            $query->where('user_id', function($subquery) use ($request) {
                $subquery->select('id')
                        ->from('users')
                        ->where('email', $request->email)
                        ->first();
            });
        }])->where('id', $request->id)->first();

        if (!$agreement) {
            return response()->json([
                'status' => false,
                'message' => 'Agreement not found'
            ], 404);
        }

       // Get signatures from both users
        $signatures = $agreement->signStatus->map(function($status) {
            $signatureUrl = null;
            if ($status->signature && $status->signature !== 'none') {
                $signatureUrl = Storage::disk('public')->url($status->signature);
            }
            return [
                'user_id' => $status->user_id,
                'signature_url' => $signatureUrl,
                'status' => $status->status
            ];
        });
        $signatre_status = $signatures[0]['user_id'] == $agreement->user_id ? $signatures[0]['status'] : null;

        // Format the agreement data with formatted dates
        $formattedAgreement = [
            'id' => $agreement->id,
            'user_id' => $agreement->user_id,
            'title' => $agreement->title,
            'slug' => $agreement->slug,
            'agreement_file' => $agreement->agreement_file,
            'signature_url1' => $signatures[0]['signature_url'] ?? null,
            'signature_url2' => $signatures[1]['signature_url'] ?? null,
            'signature_status' => $signatre_status,
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
            else{
                // Create a new sign status record for this user
                $signStatus = new SignStatus();
                $signStatus->user_id = $user->id;
                $signStatus->agreement_id = $agreement->id;
                $signStatus->status = 'pending';
                $signStatus->save();
                $sharedWith = $email;
            }
        $signStatuses = SignStatus::where('agreement_id', $agreement->id)->get();

        foreach ($signStatuses as $signStatus) {
            $signStatus->status = 'pending';
            $signStatus->save();
        }

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
                // Generate signature URL if signature exists and is not 'none' or 'true'
                $signatureUrl = null;
                if ($signStatus->signature && $signStatus->signature !== 'none' && $signStatus->signature !== 'true') {
                    $filename = basename($signStatus->signature);
                    $signatureUrl = url('/api/signature/' . $filename);
                }

                $users[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $signStatus->status,
                    'signature' => $signStatus->signature,
                    'signature_url' => $signatureUrl,
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
    public function signAgreement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agreement_id' => 'required|exists:agreements,id',
            'email' => 'required|email|exists:users,email',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        $signStatus = SignStatus::where('agreement_id', $request->agreement_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$signStatus) {
            return response()->json([
                'status' => false,
                'message' => 'User does not have access to this agreement'
            ], 403);
        }

        // Handle signature image upload
        if ($request->hasFile('signature')) {
            // Delete old signature if exists
            if ($signStatus->signature && $signStatus->signature !== 'none' && $signStatus->signature !== 'true') {
                Storage::disk('public')->delete($signStatus->signature);
            }

            $signatureFile = $request->file('signature');
            $fileName = 'signature_' . $user->id . '_' . $request->agreement_id . '_' . time() . '.' . $signatureFile->getClientOriginalExtension();
            $signaturePath = $signatureFile->storeAs('signatures', $fileName, 'public');

            $signStatus->signature = $signaturePath;
        } else {
            $signStatus->signature = 'true';
        }

        $signStatus->save();

        // Get all sign statuses for this agreement that have been signed (signature is not 'none')
        $allSignStatuses = SignStatus::where('agreement_id', $request->agreement_id)
            ->where('signature', '!=', 'none')
            ->get();

        // Check if there are multiple signers and all have signed
        if ($allSignStatuses->count() > 1 && $allSignStatuses->every(function($status) {
            return $status->signature !== 'none';
        })) {
            $allSignStatuses->each(function($status) {
                $status->status = 'complete';
                $status->save();
            });
        }

        return response()->json([
            'status' => true,
            'message' => 'Agreement signed successfully'
        ], 200);

    }
    public function declineAgreement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agreement_id' => 'required|exists:agreements,id',
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        $signStatus = SignStatus::where('agreement_id', $request->agreement_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$signStatus) {
            return response()->json([
                'status' => false,
                'message' => 'User does not have access to this agreement'
            ], 403);
        }

        $signStatus->status = 'declined';
        $signStatus->save();
        $otherSignStatuses = SignStatus::where('agreement_id', $request->agreement_id)
            ->where('user_id', '!=', $user->id) ->where('status', 'pending')
            ->get();

        // Check if there are multiple signers and all have declined
        if ($otherSignStatuses->count() > 0 ) {
            $otherSignStatuses->each(function($status) {
                $status->status = 'rejected';
                $status->save();
            });
        }
        return response()->json([
            'status' => true,
            'message' => 'Agreement declined successfully'
        ], 200);
    }

    /**
     * Serve signature image
     */
    public function getSignatureImage($filename)
    {
        $path = 'signatures/' . $filename;

        if (!Storage::disk('public')->exists($path)) {
            return response()->json([
                'status' => false,
                'message' => 'Signature image not found'
            ], 404);
        }

        $fullPath = Storage::disk('public')->path($path);
        $mimeType = mime_content_type($fullPath);
        $file = Storage::disk('public')->get($path);

        return response($file, 200)->header('Content-Type', $mimeType);
    }

}
