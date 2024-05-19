<?php

namespace App\Traits;

use App\Models\AssignedTask;
use App\Models\FavoriteProjects;
use App\Models\ProjectParticipants;
use App\Models\Projects;
use App\Models\Roles;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

trait PermissionTrait
{

    public function checkAdmin($userId){
        $users = User::where("id", $userId)->first();
        $roles = $users->roles()->pluck('role_name')->toArray();
        return in_array("Admin", $roles);
    }
    public function checkManager($userId){
        $users = User::where("id", $userId)->first();
        $roles = $users->roles()->pluck('role_name')->toArray();
        return in_array("Manager",$roles);
    }
    public function checkProjectManagerRole($projectId, $userId){
        return Projects::where(["p_manager_id"=>$userId, "id"=>$projectId])->exists();
    }
    public function checkProjectParticipant($projectId, $userId){
        return ProjectParticipants::where(["p_id"=>$projectId, "user_id" => $userId])->exists();
    }
    public function findUserAsParticipant($projectId, $userId){
        return ProjectParticipants::where(["p_id"=>$projectId, "user_id" => $userId])->first();
    }
    public function checkFavoriteProject($addedBy, $projectId){
        return FavoriteProjects::where(["added_by"=> $addedBy, "project_id"=>$projectId])->exists();
    }
    public function checkTaskAssigned($projectParticipantId, $taskId){
        return AssignedTask::where(["p_participant_id"=> $projectParticipantId,"task_id"=> $taskId])->exists();
    }
    public function usersAndRoles(){
        $users =  User::where("status", "active")->get();
        $success=[];
        foreach($users as $user){
            $roles = $user->roles()->get();
            $roleNames = $roles->pluck('role_name');

            $success[] = [
                "id" => $user->id,
                "name" => $user->name,
                "email" => $user->email,
                "roles" => $roleNames,
            ];
        }

        if(!empty($success)){
            return $success;
        }else{
            return false;
        }
    }

}
