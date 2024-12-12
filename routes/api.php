<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\ReciepientController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/sign-up', [AuthController::class, 'signUp']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/user', function (Request $request) {
   $user = $request->user();

   $transactions = $user->transactions;
  
    
    return response()->json(['user'=>$user, 'transactions'=>$transactions], 200);
})->middleware('auth:sanctum');
Route::get('/user/requests', function(Request $request){
$user = $request->user();
$requests = $user->paymentRequests;
$balance = $user->balance;
return response()->json(['requests'=>$requests, 'balance'=>$balance], 200);
})->middleware('auth:sanctum');
Route::get('/user/all', [UserController::class, 'getAllUsers'])->middleware('auth:sanctum');
Route::post('/recipient/create', [ReciepientController::class, 'createRecipient'])->middleware('auth:sanctum');
Route::post('/recipients', [ReciepientController::class, 'findRecipient'])->middleware('auth:sanctum');
Route::post('/user/find', [UserController::class, 'findUser'])->middleware('auth:sanctum');
Route::post('/user/transfer-voucher', [UserController::class, 'transferVoucher'])->middleware('auth:sanctum');
Route::post('/user/top-up', [UserController::class, 'topUpVoucher'])->middleware('auth:sanctum');
Route::post('/update-payment-request', [UserController::class, 'updateRequest'])->middleware('auth:sanctum');
// Route::post('/user/update-user', [UserController::class, 'updateUser'])->middleware('auth:sanctum');
Route::post('/user/check-password', [UserController::class, 'checkPassword'])->middleware('auth:sanctum');
Route::post('/user/upload-details', [UserController::class, 'uploadDetails'])->middleware('auth:sanctum');
Route::post('/user/update-profile', [UserController::class, 'updateProfile'])->middleware('auth:sanctum');
Route::post('/user/verify-number', [UserController::class, 'verifyNumber'])->middleware('auth:sanctum');
Route::patch('/user/update-kyc', [UserController::class, 'updateKyc'])->middleware('auth:sanctum');
Route::post('/user/create-payment-request', [UserController::class, 'createPaymentRequest'])->middleware('auth:sanctum');
Route::post('/transaction', [TransactionController::class, 'createTransaction'])->middleware('auth:sanctum');
Route::get('/transaction/all', [TransactionController::class, 'getAllTransactions'])->middleware('auth:sanctum');
Route::get('/transaction/today', [TransactionController::class, 'getTodayTransactions'])->middleware('auth:sanctum');
Route::post('/beneficiary', [BeneficiaryController::class, 'createBeneficiary'])->middleware('auth:sanctum');
Route::get('/beneficiary', [BeneficiaryController::class, 'getBeneficiaries'])->middleware('auth:sanctum');
Route::get('/payout-requests/all',function(){
    $requests = PaymentRequest::with('user')->get();

    return response()->json(['requests'=>$requests]);
} )->middleware('auth:sanctum');


