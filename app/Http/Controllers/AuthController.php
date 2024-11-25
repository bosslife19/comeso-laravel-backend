<?php

namespace App\Http\Controllers;

use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function signUp(Request $request)
    {
        try {
            //code...
            $data = $request->validate(['email' => 'required', 'name' => 'required', 'password' => 'required','phone'=>'required|string']);
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone'=>$data['phone'],
                'password' => bcrypt($data['password']),
            ]);

            $token =  $user->createToken('main')->plainTextToken;

            return response()->json(['token' => $token, 'user'=>$user], 200);
        } catch (\Exception $e) {

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request){
        $credentials = $request->validate([
            'email'=>'required',
            'password'=>'required'
        ]);

        if(!Auth::attempt($credentials)){
            return response([
                'message' =>'Provided email address or password is incorrect'
            ],422);
        }

         $user = Auth::user();
         $token = $user->createToken('main')->plainTextToken;
        

       return response(
        [
            'status'=>true,
            'token'=>$token,
            
        ]
        );
    }
}
