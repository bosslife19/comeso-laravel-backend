<?php

namespace App\Http\Controllers;

use App\Mail\KYCDone;
use App\Mail\ReceivedPayment;
use App\Mail\RequestAccepted;
use App\Mail\SentVoucher;
use App\Models\Notification;
use App\Models\PaymentRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class UserController extends Controller
{
    public $baseUrl = 'https://api.mycomeso.com';
    public function findUser(Request $request){
        
        $request->validate(['name'=>'required|string']);
        $user = User::where('name', $request->name)->first();
      
        if($user){
            if($user->name == $request->user()->name){
                return response()->json(['error'=>'Cannot perform any operations on this user!']);
            }
        }
       
        if(!$user){
            return response()->json(['error'=>'User not found!'], 200);
        }
        return response()->json(['user'=>$user], 200);
    }
    public function updateUser(Request $request){
        $request->validate(['name'=>'required', 'email'=>'required', 'phone'=>'required']);
        $user = $request->user();
        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->save();

        return response()->json(['status'=>true], 200);
    }
    public function updateKyc(Request $request){
        $user = $request->user();
        $user->kycCompleted = true;
        $user->save();
        Mail::to($user->email)->send(new KYCDone($user->name));
        return response()->json(['status'=>true], 200);
    }
    public function getPending(){
        $requests = PaymentRequest::where('status', 'pending')->get();

        return response()->json(['requests'=>$requests], 200);
    }
    public function transferVoucher(Request $request){
       
        $request->validate(['amount'=>'required','receiver'=>'required']);
        $amount = intval($request->amount);
        
        $receipient = User::where('name', $request->receiver)->first();
        $user = $request->user();
        $receipient->balance = $receipient->balance + $amount;
        $receipient->save();

        $id = random_int(10000,99999);
        $receipient->transactions()->create([
            'type'=>'Transfer In',
            'status'=>'Received',
            'amount'=>$amount,
            'transaction_id'=>$id,
            'beneficiary'=>$receipient->name,
            'sender'=>$request->user()->name,
            'phone'=>$request->user()->phone,
        ]);
        Notification::create(['title'=>"You received a payment of $amount from $user->name", 'user_id'=>$receipient->id]);
       
        $date = now();
        Mail::to($receipient->email)->send(new ReceivedPayment($receipient->name,$user->name, $amount,$date,$id ));
        
        $user->balance = $user->balance - $amount;
        $user->save();
        $user->transactions()->create([
            'type'=>'Transfer Out',
            'status'=>'Sent',
            'amount'=>$amount,
            'transaction_id'=>$id,
            'beneficiary'=>$receipient->name,
            'sender'=>$user->name,
            'phone'=>$user->phone
        ]);
        // $user->notifications()->create([
        //     'title'=>"You just sent $amount to $receipient->name"
        // ]);
        Notification::create(['title'=>"You just sent $amount to $receipient->name", 'user_id'=>$user->id]);
        Mail::to($user->email)->send(new SentVoucher($user->name,$receipient->name, $amount,$date,$id ));
        return response()->json(['status'=>true]);

    }

    public function verifyEmail(Request $request){
        $request->validate(['email'=>'required']);
    }

    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function getAllNotifications(Request $request){
        $user = $request->user();
        
        $notifications = $user->notifications()->latest()->get();

        return response()->json(['notifications'=>$notifications], 200);
    }

    public function setNotificationsTrue(Request $request)
{
    $request->validate(['status' => 'required']);

    $user = $request->user();

    // Update all notifications for the user
    $user->notifications()->update(['opened' => $request->status]);

    return response()->json(['status' => true], 200);
}

public function complain (Request $request){
    $request->validate(['name'=>'required', 'email'=>'required', 'complain'=>'required']);
    Mail::to(['support@mycomeso.com', 'wokodavid001@gmail.com'])->send(new \App\Mail\Complain($request->name, $request->email, $request->complain));

    return response()->json(['status'=>true], 200);
}


    public function updateRequest(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'status' => 'required|string',
            'name' => 'required|string',
            'token' => 'required|string',
        ]);
    
        // Fetch the user by name
        $user = User::where('name', $validated['name'])->first();
    
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
    
        // Fetch the payment request associated with the user and token
        $currentRequest = $user->paymentRequests()->where('token', $validated['token'])->first();
    
        if (!$currentRequest) {
            return response()->json(['message' => 'Payment request not found'], 404);
        }
    
        // Update the status and save
        $currentRequest->status = $validated['status'];
        $currentRequest->save();
        Mail::to($user->email)->send(new RequestAccepted($user->name));
        
    
        return response()->json(['message' => 'Payment request updated successfully', 'status'=>true], 200);
    }
    

    public function getAllUsers(Request $request){
        $user = $request->user();
        $users = User::where('email', '!=', $user->email)->get();
        $facilities = User::where('company_name', '!=', null)->get();

        return response()->json(['users'=>$users, 'facilities'=>$facilities], 200);

    }
    public function requestPasswordReset(Request $request){
        $request->validate(['email'=>'required']);
        $user = User::where('email', $request->email)->first();

        if(!$user){
            return response()->json(['error'=>'User does not exist!'], 200);
        }

        // Generate a 4-digit OTP
        $otp = random_int(1000, 9999);
    
        // Save OTP and its expiration time
        $user->update([
            'password_otp' => $otp,
            
        ]);
    
        // Send OTP via email
         Mail::to($user->email)->send(new \App\Mail\SendOtpMail($otp));
    
        return response()->json(['message' => 'OTP sent to your email.']);
    }

    public function validatePasswordOtp(Request $request){

        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp_code' => 'required|digits:4',
        ]);
    
        $user = User::where('email', $request->email)->first();
       
        // Check OTP validity
        
        if ($user->password_otp == intval($request->otp_code)) {
            // OTP is valid
            
            $user->update(['password_otp' => null]);
    
            
    
            return response()->json(['message' => 'OTP verified.'],200);
        }
    else{
        return response()->json(['message' => 'Invalid or expired OTP.'], 422);
    }
       
    }

    public function changePassword(Request $request){
        $request->validate(['password'=>'required']);
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();
        return response()->json(['status'=>true], 200);
    }
    
    public function topUpVoucher(Request $request){
        $request->validate(['amount'=>'required']);
        $user = $request->user();
        $user->balance = $user->balance + $request->amount;
        $user->save();
        $user->transactions()->create([
            'type'=>'Top-up',
            'status'=>'Received',
            'amount'=>$request->amount
        ]);
        $user->notifications()->create([
            'title'=>"You have successfully topped up your voucher with $request->amount"
        ]);

        return response()->json(['status'=>true]);
    }

    public function createPaymentRequest(Request $request){
        $request->validate(['token'=>'required', 'phone'=>'required', 'amount'=>'required']);
        $user = $request->user();
        $user->paymentRequests()->create($request->all());

        return response()->json(['status'=>true], 200);
    }
    public function verifyNumber(Request $request){
         $request->validate([
            'phone'=>'required'
        ]);
        $user = $request->user();
        if($user->phone == $request->phone){
            return response()->json(['user'=>$user], 200);
        }else{
            return response()->json(['error'=>'Phone number not found!']);
        }
    }

    public function checkPassword(Request $request)
{
    $request->validate([
        'password' => 'required',
    ]);

    $user = $request->user(); // Get the currently authenticated user

    if (Hash::check($request->password, $user->password)) {
        return response()->json([
            'status' =>true,
        ], 200);
    } else {
        return response()->json([
            'status' => false,
        ], 200);
    }
}

public function uploadDetails(Request $request){

$request->validate(['file'=>'required', 'fileType'=>'required|string']);
$file = $request->file('file')->store('certificates', 'public');
$fileUrl = "$this->baseUrl/storage/$file";

if($request->fileType =='proofOfReg'){
   $user = $request->user();
   $user->proof_of_registration = $fileUrl;
   $user->save();

}
elseif($request->fileType=='certOfComp'){
    $user = $request->user();
   $user->certificate_and_compliance = $fileUrl;
  
   $user->save();
}
elseif($request->fileType=='healthComp'){
    $user = $request->user();
   $user->health_regulations_compliance = $fileUrl;
   $user->save();
}
elseif($request->filetype =='proofOfLoc'){
    $user = $request->user();
    $user->proof_of_location = $fileUrl;
    $user->save();
}
if($request['fileType'] =='regDoc'){
   
    $user = $request->user();
    $user->registration_document = $fileUrl;
   
    $user->save();
    
}elseif($request['fileType'] =='logo'){
    $user = $request->user();
    $user->company_logo = $fileUrl;
   
    $user->save();
}



 return response()->json(['status'=>true]);
}

public function updateProfile(Request $request){
if($request->name){
    $user = $request->user();
    $user->name = $request->name;
    $user->save();
}
if($request->jobTitle){
    $user = $request->user();
    $user->job_title = $request->jobTitle;
    $user->save();
}
if($request->bank){
   
    $user = $request->user();
    $user->bank_name = $request->bank;
   

    $user->save();
}
if($request->accountNumber){
    $user = $request->user();
    $user->account_number = $request->accountNumber;
    $user->save();
}
// name, companyName, numPatients, numStaff, revenue,email,
if($request->numPatients){
    $user = $request->user();
    $user->number_of_patients = $request->numPatients;
    $user->save();
}
if($request->numStaff){
    $user = $request->user();
    $user->number_of_staff = $request->numStaff;
    $user->save();
}
if($request->revenue){
    $user = $request->user();
    $user->yearly_revenue = $request->revenue;
    $user->save();
}
if($request->email){
    $user = $request->user();
    $user->email= $request->email;
    $user->save();
}
if($request->companyName){
    $user = $request->user();
    $user->company_name = $request->companyName;
    $user->save();
}

if($request->file('herfa')){
    $file = $request->file('herfa')->store('certificates', 'public');
    $fileUrl = "$this->baseUrl/storage/$file";
    $user = $request->user();
    $user->health_regulations_compliance = $fileUrl;
    $user->save();
}
if($request->file('coc')){
    $file = $request->file('coc')->store('certificates', 'public');
    $fileUrl = "$this->baseUrl/storage/$file";
    $user = $request->user();
    $user->certificate_and_compliance = $fileUrl;
    $user->save();
}
if($request->file('regDoc')){
    $file = $request->file('regDoc')->store('certificates', 'public');
    $fileUrl = "$this->baseUrl/storage/$file";
    $user = $request->user();
    $user->registration_document = $fileUrl;
    $user->save();
}
if($request->file('complogo')){
    $file = $request->file('complogo')->store('certificates', 'public');
    $fileUrl = "$this->baseUrl/storage/$file";
    $user = $request->user();
    $user->company_logo = $fileUrl;
    $user->save();
}
if($request->currentPassword){
    $user = $request->user();
    $isCorrect = Hash::check($request->currentPassword, $user->password);
    if($isCorrect){
        $user->password = Hash::make($request->newPassword);
        $user->save();
    }else{
        return response()->json(['error'=>'Current Password is not valid']);
    }
}


return response()->json(['status'=>true], 200);
}
}
