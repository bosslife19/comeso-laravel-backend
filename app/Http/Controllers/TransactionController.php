<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function createTransaction(Request $request){
        

            $validated = $request->validate([
                'type'=>'required|string',
                'status'=>'required',
                'amount'=>'required',
            ]);

            Transaction::create([
                'type'=>$validated['type'],
                'status'=>$validated['status'],
                'amount'=>$validated['amount'],
                'user_id'=>$request->user()->id,
            ]);

            return response()->json(['status'=>true], 200);
    }
}
