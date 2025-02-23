<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Agreement;
use App\Models\SignStatus;
use Illuminate\Http\Request;

class AgreementController extends Controller
{
    public function createAgreement(Request $request) {
        $agreement = new Agreement();
        $agreement->user_id = User::where('email', $request->email);
        $agreement->title = $request->title;
        $agreement->agreement_file = $request->agreement_file;
        if($request->signature){
            $sign_status = new SignStatus();
            $sign_status->user_id = $agreement->user_id;
            $sign_status->agreement_id = $agreement->id;
            $sign_status->signature = $request->signature;
            $sign_status->save();
        }
        $agreement->save();
        return response()->json(['message' => 'Agreement created successfully'], 200);
    }

}
