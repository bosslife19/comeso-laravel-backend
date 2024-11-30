<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
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

Route::post('/user/find', [UserController::class, 'findUser'])->middleware('auth:sanctum');
Route::post('/user/transfer-voucher', [UserController::class, 'transferVoucher'])->middleware('auth:sanctum');

Route::post('/transaction', [TransactionController::class, 'createTransaction'])->middleware('auth:sanctum');
Route::post('/beneficiary', [BeneficiaryController::class, 'createBeneficiary'])->middleware('auth:sanctum');
Route::get('/beneficiary', [BeneficiaryController::class, 'getBeneficiaries'])->middleware('auth:sanctum');

