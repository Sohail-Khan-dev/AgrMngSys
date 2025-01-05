<?php

namespace App\Http\Controllers;

use App\Mail\OTPMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class emailController extends Controller
{
    public function SendWelcomeEmail(Request $request){
        // Below is the dummy record we will edit it if required as we need it. 
        $otp = rand(100000,999999);
        Mail::to("sohail8338@gmail.com")->send(new OTPMail($otp));
        return response()->json(['msg'=>"Email Sent Successfully", "otp" => $otp]);
    }

}
