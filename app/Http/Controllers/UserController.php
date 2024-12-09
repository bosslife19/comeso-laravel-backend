<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

class UserController extends Controller
{
    public $baseUrl = 'http://localhost:8000';
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

    public function transferVoucher(Request $request){
       
        $request->validate(['amount'=>'required','receiver'=>'required']);
        $amount = intval($request->amount);
        
        $receipient = User::where('name', $request->receiver)->first();
       
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
        $user = $request->user();
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

        return response()->json(['status'=>true]);

    }

    public function getAllUsers(Request $request){
        $user = $request->user();
        $users = User::where('email', '!=', $user->email)->get();
        $facilities = User::where('company_name', '!=', null)->get();

        return response()->json(['users'=>$users, 'facilities'=>$facilities], 200);

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
   \Log::info($request->fileType);
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
