<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ButtonController\Exception;
use App\Http\Controllers\PermissionController as Permission;
use App\Models\Projects;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class ButtonController extends Controller
{
    public function getProjectandTaskButtons($ProjectId){
        $user= JWTAuth::parseToken()->authenticate();
        $json_data=Storage::get("buttons/buttons.json");
        $buttons = json_decode($json_data, true);
        $ManagerButtons = $buttons["manager"];
        $EmployeeButtons = $buttons["employee"];
        $AdminButtons = $buttons["admin"];
        $success=[];
        $superUser=[];

        $getProject = Projects::where("id", $ProjectId)->first();
        $haveProjectManagerRole = Permission::checkManager($user->id);
        $haveAdminRole = Permission::checkAdmin($user->id);
        if($getProject["p_manager_id"] == $user->id && $haveProjectManagerRole === true){

            $haveProjectParticipantRole = true;

            $success[]=[
                "manager"=>$ManagerButtons,
                "employee"=>$EmployeeButtons,
                "message"=> "Welcome, Manager!"
            ];

        }else{
            $haveProjectParticipantRole = Permission::checkProjectParticipant($ProjectId, $user->id);
            if($haveProjectParticipantRole===true){
                $success[]=[
                    "employee"=> $EmployeeButtons,
                    "message"=> "You can access!"
                ];
            }else if($haveAdminRole===false){
                throw new Exception("You have no access permission!");
            }
        }

        if (
            $haveAdminRole ===true &&
            $haveProjectParticipantRole ===true &&
            $haveProjectManagerRole ===true
        ){

            $superUser[]=[
                "employee"=>$EmployeeButtons,
                "manager"=>$ManagerButtons
            ];

            return response()->json($superUser,200);
        }else if($haveAdminRole === true){

            $admin[]=[
                "admin"=>$AdminButtons,
            ];
            return response()->json($admin,200);
        }

        return response()->json($success,200);
    }

    public function getUsersButton(){
        $user=JWTAuth::parseToken()->authenticate();
        $json_data=Storage::get("buttons/buttons.json");
        $buttons = json_decode($json_data, true);
        $AdminButtons = $buttons["admin"];

        $haveAdminRole = Permission::checkAdmin($user->id);
        if($haveAdminRole === true){
            $success=[
                "admin"=>$AdminButtons,
                "message"=>"You can access to admin buttons!"
            ];
        }else{

            throw new \Exception("Access Denied");
        }

        return response()->json($success,200);
    }

}
