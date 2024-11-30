<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function findUser(Request $request){
        $request->validate(['name'=>'required|string']);
        $user = User::where('name', $request->name)->first();
        if(!$user){
            return response()->json(['error'=>'User not found!'], 404);
        }
        return response()->json(['user'=>$user], 200);
    }

    public function transferVoucher(Request $request){
       
        $request->validate(['amount'=>'required','receiver'=>'required']);
        $amount = intval($request->amount);
        
        $receipient = User::where('name', $request->receiver)->first();
       
        $receipient->balance = $receipient->balance + $amount;
        $receipient->save();
        $receipient->transactions()->create([
            'type'=>'Transfer In',
            'status'=>'Received',
            'amount'=>$amount
        ]);
        $user = $request->user();
        $user->balance = $user->balance - $amount;
        $user->save();
        $user->transactions()->create([
            'type'=>'Transfer Out',
            'status'=>'Sent',
            'amount'=>$amount
        ]);

        return response()->json(['status'=>true]);

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
}
