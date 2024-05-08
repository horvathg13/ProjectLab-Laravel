<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{


    public function login(Request $request){
        if (Auth::attempt(["email"=>$request->email, "password"=>$request->password])) {

            $finduser= User::where("email", $request->email)->first();
            if($finduser->status !== "Banned"){
                $user = Auth::user();
                $token = JWTAuth::fromUser($user);
                $success = [];
                $success['name'] = $user->name;
                $success['id'] = $user->id;
                $success['email'] = $user->email;
                $success["token"]= $token;

                Auth::login($user, true);

                $response = [
                    "success"=>true,
                    "data"=>$success,
                    "message"=>"User Login Successfull",
                ];
                return response()->json($response);
            }else{
                $response = [
                    "success" => false,
                    "message" => "You are banned!"
                ];
                return response()->json($response, 401);
            }

        }else{
            $response = [
                "success" => false,
                "message" => "Invalid e-mail or password"
            ];
            return response()->json($response, 401);
        }

    }

    public function logout(){
        try{
            JWTAuth::parseToken()->invalidate();
        } catch(\Exception $ex){
        }

        $response = [
            "success"=>true,
            "message"=>"User Logout Successfull",
        ];
        return response()->json($response);

    }
}
