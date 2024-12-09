<?php

namespace App\Http\Controllers;

use App\Models\Reciepient;
use Illuminate\Http\Request;

class ReciepientController extends Controller
{
    public function createRecipient(Request $request){
        Reciepient::create([
            'name'=>$request->name,
            'reciepient_code'=>$request->reciepient_code,
            'bank_code'=>$request->bank_code,
            'account_number'=>$request->account_number
        ]);
        return response()->json(['status'=>true], 200);
    }
}
