<?php

namespace App\Http\Controllers\RegisterControllers;

use App\Http\Controllers\Controller;
use App\Models\PasswordResets;
use App\Models\Roles;
use App\Models\RoleToUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;



class RegisterController extends Controller
{
    public function register(Request $request){
        return DB::transaction(function() use(&$request){
            $validator = Validator::make($request->all(),[
                "name" => "required",
                "email" => "required|email|unique",
                "password" => "required",
                "confirm_password" => "required|same:password"
            ]);

            if ($validator->fails()){
                $response=[
                    "validatorError"=> $validator->errors()->all()
                ];
                return response()->json($response, 422);
            }


            $input = [
                "name" =>$request->name,
                "email"=>$request->email,
                "password" =>bcrypt($request->password),
                "status"=>"active"
            ];

            $user = User::create($input);
            $success["token"]= $user->createToken("Procejt-Lab-Token")->plainTextToken;
            $success["name"] =$user->name;


            $response = [
                "success"=>true,
                "data"=>$success,
                "message"=>"User register Successful"
            ];

            return response()->json($response,200);
        });
    }

    public function createUser(Request $request){
        return DB::transaction(function() use(&$request){
            $validator = Validator::make($request->all(),[
                "name" => "required",
                "email" => "required|email|unique",
            ]);

            if ($validator->fails()){
                $response=[
                    "validatorError"=>$validator->errors()->all(),
                ];
                return response()->json($response, 400);
            }

            $name = $validator->validated()['name'];
            $email = $validator->validated()['email'];
            $token = Str::random(60);

            $user_cresendals=[
                "name" => $name,
                "email" => $email,
                "status" =>"active"
            ];

            User::create($user_cresendals);

            $cresendals = [
                'email' => $email,
                'token' => $token,
            ];

            PasswordResets::create($cresendals);

            $success=[
                "url" =>env("FRONTEND_URL").'/reset-password/'.$token,
                "name" => $name,
                "email" => $email,
            ];


            $response = [
                "success"=>true,
                "data"=>$success,
                "message"=>"User created Successfull"
            ];

            return response()->json($response,200);
        });
    }

    public function findUser($token){
        $passwordReset = PasswordResets::where('token', $token)->first();

        if (!$passwordReset) {

            return response()->json(['error' => 'Invalid token'], 404);
        }

        $email = $passwordReset->email;

        $username = User::where('email', $email)->first();

        if($username){
            $success=[
                "name" => $username->name,
                "email" => $email,
                "success" => true,
            ];

            return response()->json($success);
        }else{
            $success=[
                "error" => "Invalid email"
            ];
            return response()->json($success);
        }


    }

    public function resetPassword(Request $request){
        return DB::transaction(function() use(&$request){
            $validator = Validator::make($request->all(),[
                "email" => "required|email",
                "password" => "required",
                "confirm_password" => "required|same:password"
            ]);

            if ($validator->fails()){
                $response=[
                    "success" => false,
                    "message"=> $validator->errors()
                ];
                return response()->json($response, 400);
            }

            $password = $validator->validated()['password'];
            $email = $validator->validated()['email'];
            $find = User::where('email', $email)->first();

            if($find){

                $find->password = bcrypt($password);
                $find->status = "active";
                $find->save();
                PasswordResets::where('email', $email)->delete();


                $success=[
                    "message" => "Password Reset Successfull",
                    "status" => 200
                ];
                return response()->json($success);
            }else{
                $success=[
                    "message" => "Invalid email",
                    "status" => 500
                ];

                return response()->json($success);
            }
        });
    }

    public function passwordResetManual($id){
        return DB::transaction(function() use(&$id){
            $finduser= User::find($id);
            $finduser->password = null;
            $finduser->remember_token = null;
            $finduser->status="temporary deactivate";
            $finduser->save();


            if(!$finduser){
                $success=[
                    "message" => "User does not exist",
                    "status" => 500
                ];
                return response()->json($success);
            }

            $token = Str::random(60);
            $cresendals= [
                "email"=>$finduser->email,
                "token"=>$token
            ];
            //A USERS táblában érdemes lenne egy státuszállítás ezen a ponton

            PasswordResets::create($cresendals);

            $success=[
                "url" =>env("FRONTEND_URL")."/reset-password/".$token,
                "name" => $finduser->name,
                "email" => $finduser->email,
            ];


            $response = [
                "success"=>true,
                "data"=>$success,
                "message"=>"User created Successfull"
            ];


            return response()->json($response,200);
        });


    }

    public function bannTheUser($id){
        return DB::transaction(function() use(&$id){
            $finduser= User::find($id);
            $finduser->status="Banned";
            $finduser->save();


            if(!$finduser){
            $success=[
                "message" => "User does not exist",
                "status" => 500
            ];
            return response()->json($success);
            }
        });
    }

}
