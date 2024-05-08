<?php

namespace App\Http\Controllers;

use App\Http\Controllers\PermissionController as Permission;
use App\Models\Roles;
use App\Models\RoleToUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PHPUnit\Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    public function getUserData(){
        try{
            $user = JWTAuth::parseToken()->authenticate();
        } catch(\Exception $ex){
            $user = new \stdClass();
        }
        return response()->json($user);
    }
    public function activeUsers(){
        try{
            return User::where("status", "active")->get();
        }catch (Exception $e){
            return $e;
        }
    }

    public function getUsers(){
        $success = Permission::usersAndRoles();
        if($success){
            foreach ($success as &$u){
                $convert= $u["roles"]->implode(", ", $u['roles']);
                $u['roles']= $convert;
            }
            return response()->json($success);
        }
    }
    public function getManagers(){
        $users = Permission::usersAndRoles();;
        if($users){
            $success=[];
            foreach($users as $user){
                if($user['roles']->contains('Manager') ){
                    $success[]=[
                        "id"=>$user['id'],
                        "name"=>$user['name'],
                        "email"=>$user['email'],
                        "roles"=>$user['roles'],
                    ];
                }
            }

            if(!empty($success)){
                return response()->json($success,200);
            }else{
                $success=[
                    "message"=>"Database error",
                    "code"=>404,
                ];
                return response()->json($success);
            }
        }else{
            throw new \Exception('Database error occured!');
        }
    }
    public function getEmployees(Request $request){

        $users = Permission::usersAndRoles();;
        if($users) {
            $allEmployee=[];
            foreach ($users as $user) {
                if ($user['roles']->contains('Employee')) {
                    $allEmployee[] = [
                        "id" => $user['id'],
                        "name" => $user['name'],
                        "email" => $user['email'],
                        "roles" => $user['roles'],
                    ];
                }
            }
            if (!empty($allEmployee)) {
                return response()->json($allEmployee, 200);
            } else {
                $fail = [
                    "message" => "Database error",
                    "code" => 404,
                ];
                return response()->json($fail);
            }
        }
    }

    public function userToRole(Request $request){
        return DB::transaction(function() use(&$request){

            $checkUser=JWTAuth::parseToken()->authenticate();
            $haveAdminRole = Permission::checkAdmin($checkUser->id);
            if($haveAdminRole){
                $selectedRoles = $request->input('selectedRole');
                $id = $request->input('user_id');
                $remove= $request->input('remove');

                $data=[
                    "selectedRole"=>$selectedRoles,
                    "user_id"=>$id
                ];
                $rules=[
                    "selectedRole"=>"nullable",
                    "user_id"=>"required",
                ];
                $validator = Validator::make($data, $rules);


                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }
                if(empty($selectedRoles) && empty($remove)){
                    throw new \Exception("Opration canceld!");
                }
                $findUser = User::find($id);

                if (!$findUser){
                    $success=[
                        "message"=>"Invalid User",
                        "code"=>404,
                    ];
                    return response()->json($success);
                }
                $success=[];
                if(count($selectedRoles)>0){
                    foreach($selectedRoles as $selected){
                        $findUserRoles=RoleToUser::where(["user_id"=>$findUser->id, "role_id"=>$selected['id']])->exists();
                        if($findUserRoles===false){
                            $credentials=[
                                'user_id' => $findUser->id,
                                'role_id' => $selected['id']
                            ];

                            $role= RoleToUser::create($credentials);

                            $success[]=[
                                "message"=>"Thats it!",
                                "code"=>200,
                            ];

                        }else{
                            throw new \Exception("Role already attached to user!");
                        }
                    }
                }

                if(count($remove)>0){
                    foreach($remove as $r){
                        $findRoleId=Roles::where("role_name",$r['name'])->first();
                        $removeRole=RoleToUser::where(["user_id"=>$findUser->id, "role_id"=>$findRoleId->id])->delete();
                    }
                    $success=[
                        'message'=>"Operation Completed!"
                    ];
                    return response()->json($success);
                }
                return response()->json($success);

            }else{
                throw new \Exception("You do not have the correct role for this operation!");
            }
        });
    }
    public function saveProfileData(Request $request){
        return DB::transaction(function() use(&$request){
            $user=JWTAuth::parseToken()->authenticate();
            $name = $request->input('name');
            $email = $request->input('email');

            $validator = Validator::make($request->all(),[
                "name"=>"required",
                "email"=>"required"
            ]);
            if ($validator->fails()){
                $response=[
                    "validatorError"=>$validator->errors()->all(),
                ];
                return response()->json($response, 400);
            }
            $checkUser = User::where('id',$user->id)->exists();
            if($checkUser===false){
                throw new \Exception('User does not exitst');
            }else{
                $updateUser = User::where('id',$user->id);
                if(!empty($name)){
                    $updateUser->update([
                        "name"=>$name,
                    ]);
                }
                if(!empty($email)){
                    $updateUser->update([
                        "email"=>$email,
                    ]);
                }
                $success = "Update Successfull";
                return response()->json($success,200);
            }
        });
    }

}
