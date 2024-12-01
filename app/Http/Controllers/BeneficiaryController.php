<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BeneficiaryController extends Controller
{
    public function createBeneficiary(Request $request){
        $validated = $request->validate([
            'name'=>'required',
            'phone'=>'required',
            'email'=>'required'
        ]);

        $user = $request->user();
       
        $user->beneficiaries()->create($validated);

        return response()->json(['status'=>true], 200);
    }

    public function getBeneficiaries(Request $request){
        $user = $request->user();
        $beneficiaries = $user->beneficiaries;

        return response()->json(['beneficiaries'=>$beneficiaries], 200);
    }
}
