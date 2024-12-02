<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

use function PHPUnit\Framework\isNull;

class BeneficiaryController extends Controller
{
    public function createBeneficiary(Request $request){
        $validated = $request->validate([
            'name'=>'required',
            'phone'=>'required',
            'email'=>'required'
        ]);

        $user = $request->user();

        $beneficiary = User::where('name', $request->name);

        if(is_null($beneficiary)){
            return response()->json(['status'=>false], 200);
        }
       
        $user->beneficiaries()->create($validated);

        return response()->json(['status'=>true], 200);
    }

    public function getBeneficiaries(Request $request){
        $user = $request->user();
        $beneficiaries = $user->beneficiaries;

        return response()->json(['beneficiaries'=>$beneficiaries], 200);
    }
}
