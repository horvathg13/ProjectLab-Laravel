<?php

namespace App\Http\Controllers;

use App\Http\Controllers\PermissionController as Permission;
use App\Http\Controllers\ProjectController\Exception;
use App\Models\AssignedTask;
use App\Models\FavoriteProjects;
use App\Models\ProjectParticipants;
use App\Models\Projects;
use App\Models\ProjectsStatus;
use App\Models\Roles;
use App\Models\RoleToUser;
use App\Models\Tasks;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProjectController extends Controller
{
    public function createProject(Request $request){
        return DB::transaction(function() use(&$request){
            $user=JWTAuth::parseToken()->authenticate();
            $project_name = $request->input('project_name');
            $managerId= $request->input('manager_id');
            $date=$request->input('date');
            $project_id= $request->input('project_id');

            $data=[
                "project_name"=>$project_name,
                "manager"=>$managerId,
                "deadline"=>$date,
                "projectId"=>$project_id

            ];
            $rules=[
                "project_name"=>"required",
                "manager"=>"required",
                "deadline"=> "required",
                "projectId"=>"nullable"
            ];
            $validator = Validator::make($data, $rules);

            if ($validator->fails()){
                $response=[
                    "validatorError"=>$validator->errors()->all(),
                ];
                return response()->json($response, 400);
            }

            $haveAdminRole=Permission::checkAdmin($user->id);
            $haveManagerRole=Permission::checkManager($user->id);

            if($haveAdminRole===true || $haveManagerRole === true){
                if(!empty($project_id)){
                    $findProject = Projects::where(["id"=>$project_id])->first();
                    if($findProject != null){
                        $formattedDate=null;
                        if(Date::hasFormat($request->date, 'Y.m.d')){
                            $formattedDate = Date::createFromFormat('Y.m.d', $request->date)->format('Y-m-d');
                        }
                        $findManagerRoleId = Roles::where("role_name", "Manager")->first();
                        $findManagerGlobalRole = RoleToUser::where(["role_id"=>$findManagerRoleId->id, "user_id"=>$managerId])->exists();
                        if($findManagerGlobalRole==true){
                            $update= $findProject->update([
                                "p_name" => $project_name,
                                "p_manager_id" => $managerId,
                                "deadline" => $formattedDate ?:$request->date,
                            ]);
                            $checkManagerIsParticipant= ProjectParticipants::where(["user_id"=>$managerId, "p_id"=>$project_id])->exists();
                            if($checkManagerIsParticipant === false){
                                ProjectParticipants::create([
                                    "user_id"=>$managerId,
                                    "p_id"=>$project_id,
                                ]);
                            }
                            $success=[
                                "message"=>"Update Successfull",
                                "data"=>$update,
                            ];

                            return response()->json($success);
                        }else{
                            throw new \Exception("User has no manager role in the system!");
                        }

                    }
                }else{
                    $findManagerRoleId = Roles::where("role_name", "Manager")->first();
                    $findManagerGlobalRole = RoleToUser::where(["role_id"=>$findManagerRoleId->id, "user_id"=>$managerId])->exists();
                    if($findManagerGlobalRole===true){
                        $status = ProjectsStatus::where("p_status", "Active")->first();
                        $formattedDate=null;
                        if(Date::hasFormat($request->date, 'Y.m.d')){
                            $formattedDate = Date::createFromFormat('Y.m.d', $request->date)->format('Y-m-d');
                        }

                        $credentials=[
                            "p_name" => $project_name,
                            "p_manager_id" => $managerId,
                            "deadline" => $formattedDate ?: $request->date,
                            "p_status" => $status->id
                        ];

                        $create = Projects::create($credentials);

                        ProjectParticipants::create([
                            "user_id"=>$managerId,
                            "p_id"=>$create->id,
                        ]);

                        if(!$create){
                            $success=[
                                "message"=>"Fail under create project",
                                "code"=>404,
                            ];
                            return response()->json($success);
                        }else{
                            $success=[
                                "message"=>"Thats it!",
                                "code"=>200,
                                "date"=>$formattedDate
                            ];

                            return response()->json($success);
                        }
                    }else{
                        throw new \Exception("User has no manager role in the system!");
                    }

                }
            }else{
                throw new \Exception("Denied!");
            }

        });

    }

    public function getProjects(Request $request){

        $user=JWTAuth::parseToken()->authenticate();

        $sortData = $request->input('sortData');
        $filterData = $request->input('filterData');

        $data = [
            'sortData' => $sortData,
            'filterData'=>$filterData,
        ];

        $rules = [
            'sortData'=>'nullable',
            'filterData'=>'nullable',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $projectsQuery= Projects::query();
        if(!empty($filterData)){
            $ids=[];
            foreach($filterData as $filter){
                if(count($filter) == 1){
                    foreach($filter as $f){
                        $projectsQuery->where('p_status', $f['id']);
                    }
                }else{
                    foreach($filter as $f){
                        $ids[]=$f['id'];
                    }
                    if(!empty($ids)){
                        $projectsQuery->whereIn('p_status', $ids);
                    }
                }
            }
        }

        if(!empty($sortData)){
            foreach($sortData as $sort){
                $projectsQuery->orderBy($sort['key'], $sort['abridgement']);
            }
        }
        $projects = $projectsQuery->get();

        /*$findAdminRole = Roles::where("role_name","Admin")->pluck("id");
        $checkAdmin = RoleToUser::where(["user_id"=>$user->id, "role_id"=>$findAdminRole])->exists();*/
        $checkAdmin=Permission::checkAdmin($user->id);
        $success =[];
        if($checkAdmin===true){
            foreach($projects as $project){
                $findManager = User::where("id", $project->p_manager_id)->first();
                $findStatus = ProjectsStatus::where("id", $project->p_status)->first();
                $findFavorite = FavoriteProjects::where(["added_by"=> $user->id, "project_id"=>$project->id])->exists();

                $success[] =[
                    "project_id" => $project->id,
                    "manager_id"=>$project->p_manager_id,
                    "manager" => $findManager->name,
                    "manager_email"=>$findManager->email,
                    "name"=>$project->p_name,
                    "status"=>$findStatus->p_status,
                    "deadline"=>$project->deadline,
                    "favorite"=>$findFavorite,
                ];
            }

            if(!empty($success)){
                return response()->json($success,200);
            }else{
                throw new \Exception('Database error occured.');
            }
        }else{
            foreach($projects as $project){
                $findMyProjects = ProjectParticipants::where(["user_id"=>$user->id,"p_id"=>$project->id])->get();
                $findManager = User::where("id", $project->p_manager_id)->first();
                $findStatus = ProjectsStatus::where("id", $project->p_status)->first();
                $findFavorite = FavoriteProjects::where(["added_by"=> $user->id, "project_id"=>$project->id])->exists();
                foreach($findMyProjects as $my){
                    $success[] =[
                        "project_id" => $project->id,
                        "manager_id"=>$project->p_manager_id,
                        "manager" => $findManager->name,
                        "manager_email"=>$findManager->email,
                        "name"=>$project->p_name,
                        "status"=>$findStatus->p_status,
                        "deadline"=>$project->deadline,
                        "favorite"=>$findFavorite,
                    ];
                }
            }
            if(!empty($success)){
                return response()->json($success,200);
            }else{
                throw new \Exception("You have no attached project!");
            }

        }
    }
    public function getProjectById($id){
        $projects = Projects::where("id", $id)->first();
        $success =[];

        $findManager = User::where("id", $projects['p_manager_id'])->first();
        $findStatus = ProjectsStatus::where("id", $projects['p_status'])->first();

        $success =[
            "project_id" => $projects['id'],
            "manager" => $findManager['name'],
            "name"=>$projects['p_name'],
            "status"=>$findStatus['p_status'],
            "deadline"=>$projects['deadline']
        ];

        if(!empty($success)){
            return response()->json($success,200);
        }else{
            $success=[
                "message"=>"Database error",
                "code"=>404,
            ];
            return response()->json($success);
        }
    }
    public function getProjectParticipants($id){
        $projectParticipants= ProjectParticipants::where("p_id", $id)->get();
        $success=[];

        foreach($projectParticipants as $p){
            $findUser = User::where("id", $p->user_id)->first();
            $findProjectname = Projects::where("id", $p->p_id)->first();
            $findStatus = ProjectsStatus::where("id",$findProjectname->p_status)->first();

            $success[]=[
                "id"=>$p->id,
                "userId"=>$findUser->id,
                "name"=>$findUser->name,
                "email"=>$findUser->email,
                "project_name"=>$findProjectname->p_name,
                "status"=>$findStatus->p_status,
            ];
        }

        if(!empty($success)){
            return response()->json($success,200);
        }else{
            throw new \Exception('You have no participants in this project.');
        }
    }
    public function createParticipants(Request $request){
        return DB::transaction(function() use(&$request){
            $user=JWTAuth::parseToken()->authenticate();
            $haveManagerRole = Permission::checkManager($user->id);
            $haveAdminRole = Permission::checkAdmin($user->id);

            if($haveManagerRole===true || $haveAdminRole===true){
                $validator = Validator::make($request->all(),[
                    "participants" => "nullable",
                    "project" => "required",
                    "remove"=>"nullable"
                ]);

                if ($validator->fails()){
                    $response=[
                        "validatorError"=>$validator->errors()->all(),
                    ];
                    return response()->json($response, 400);
                }

                $participants = $request->input('participants');
                $project = $validator->validated()['project'];
                $remove = $request->input('remove');
                if(empty($participants) && empty($remove)){
                    throw new \Exception("Operation canceled!");
                }
                if(!empty($participants)){
                    foreach($participants as $parti){
                        $findParticipants = Permission::checkProjectParticipant($project['project_id'], $parti['id']);
                        if($findParticipants === true){
                            throw new \Exception("Participants already attached!");
                        }else{
                            $find_status_id = ProjectsStatus::where("p_status", $project["status"])->first();
                            ProjectParticipants::create([
                                "user_id"=>$parti['id'],
                                "p_id"=>$project['project_id'],
                            ]);
                        }

                    };
                    $success=[
                        "message"=>"That's it! Participants created Successfully",
                        "code"=>200,
                    ];
                }

                if(!empty($remove)){

                    foreach($remove as $r){
                        #$r['id'] === ProjectParticipants->id
                        $findManagerInParticipants= ProjectParticipants::where(["id"=>$r['id'],"p_id"=> $project['project_id']])->first();
                        if(empty($findManagerInParticipants)){
                            throw new \Exception("Database error: User does not found!");
                        }else{
                            $findAsManager= Permission::checkProjectManagerRole($project['project_id'], $findManagerInParticipants->user_id);
                            if($findAsManager === true){
                                throw new \Exception("You can not remove the project manager");
                            }else{
                                $findTasks = Tasks::where("p_id",$project['project_id'])->pluck('id');
                                AssignedTask::where("p_participant_id",$r['id'])->whereIn("task_id",$findTasks)->delete();
                                ProjectParticipants::where(["id"=>$r['id'],"p_id"=> $project['project_id']])->delete();
                            }
                        }
                    }
                    $success=[
                        "message"=>"That's it!",
                        "code"=>200,
                    ];
                }

                return response()->json($success,200);
            }else{
                throw new \Exception("Denied!");
            }
        });
    }
    public function addFavoriteProject(Request $request){
        $user= JWTAuth::parseToken()->authenticate();
        $projectData = $request->input('project');

        $findProjectInFavorite = Permission::checkFavoriteProject($user->id, $projectData['project_id']);
        if($findProjectInFavorite === false){
            FavoriteProjects::create([
                "added_by"=>$user->id,
                "project_id"=>$projectData['project_id']
            ]);
        }else{
            throw new \Exception("Project already added to your favorites!");
        }

        $success=[
            "message"=>"That's your favorite!"
        ];

        return response()->json($success,200);
    }
    public function removeFromFavorite(Request $request){
        $user= JWTAuth::parseToken()->authenticate();
        $projectData = $request->input('project');

        $findProjectInFavorite = Permission::checkFavoriteProject($user->id, $projectData['project_id']);
        if($findProjectInFavorite === true){
            FavoriteProjects::where(["added_by"=>$user->id, "project_id"=>$projectData['project_id']])->delete();
        }else{
            throw new \Exception("Project does not exists in your favorites!");
        }
        $success=[
            "message"=>"Project removed from favorites!"
        ];
        return response()->json($success,200);
    }

    public function getFavoriteProjects(Request $request){
        $user= JWTAuth::parseToken()->authenticate();
        $sortData = $request->input('sortData');
        $filterData = $request->input('filterData');

        $findProjectById = FavoriteProjects::where("added_by",$user->id)->pluck('project_id');

        $success=[];
        if(empty($findProjectById)){
            throw new Exception("You have no favorite project");
        }else{
            $findProject = Projects::whereIn("id",$findProjectById);
            if(!empty($filterData)){
                $ids=[];
                foreach($filterData as $filter){
                    if(count($filter) == 1){
                        foreach($filter as $f){
                            $findProject->where('p_status', $f['id']);
                        }
                    }else{
                        foreach($filter as $f){
                            $ids[]=$f['id'];
                        }
                        if(!empty($ids)){
                            $findProject->whereIn('p_status', $ids);
                        }
                    }
                }
            }

            if(!empty($sortData)){
                foreach($sortData as $sort){
                    $findProject->orderBy($sort['key'], $sort['abridgement']);
                }
            }
            $projects = $findProject->get();

            foreach($projects as $project){
                $findManager = User::where("id", $project->p_manager_id)->first();
                $findStatus = ProjectsStatus::where("id", $project->p_status)->first();
                $findFavorite = Permission::checkFavoriteProject($user->id, $project->id);
                $success[] =[
                    "project_id" => $project->id,
                    "manager_id"=>$project->p_manager_id,
                    "manager" => $findManager->name,
                    "manager_email"=>$findManager->email,
                    "name"=>$project->p_name,
                    "status"=>$findStatus->p_status,
                    "deadline"=>$project->deadline,
                    "favorite"=>$findFavorite
                ];
            }

            return response()->json($success,200);
        }
    }
    public function getManagerProjects(Request $request){
        $user=JWTAuth::parseToken()->authenticate();
        $sortData = $request->input('sortData');
        $filterData = $request->input('filterData');

        $data = [
            'sortData' => $sortData,
            'filterData'=>$filterData,
        ];

        $rules = [
            'sortData'=>'nullable',
            'filterData'=>'nullable',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $haveManagerRole = Permission::checkManager($user->id);
        if($haveManagerRole) {

            $findActiveProjects = ProjectsStatus::where("p_status", "Active")->first();
            $projectsQuery = Projects::where(["p_manager_id" => $user->id, "p_status" => $findActiveProjects->id]);

            if (!empty($filterData)) {
                $ids = [];
                foreach ($filterData as $filter) {
                    if (count($filter) == 1) {
                        foreach ($filter as $f) {
                            $projectsQuery->where('p_status', $f['id']);
                        }
                    } else {
                        foreach ($filter as $f) {
                            $ids[] = $f['id'];
                        }
                        if (!empty($ids)) {
                            $projectsQuery->whereIn('p_status', $ids);
                        }
                    }
                }
            }

            if (!empty($sortData)) {
                foreach ($sortData as $sort) {
                    $projectsQuery->orderBy($sort['key'], $sort['abridgement']);
                }
            }

            $projects = $projectsQuery->get();
            $success = [];

            foreach ($projects as $project) {
                $findManager = User::where("id", $project->p_manager_id)->first();
                $findStatus = ProjectsStatus::where("id", $project->p_status)->first();
                //$findFavorite = FavoriteProjects::where(["added_by"=> $user->id, "project_id"=>$project->id])->exists();
                $findFavorite = Permission::checkFavoriteProject($user->id, $project->id);
                $success[] = [
                    "project_id" => $project->id,
                    "manager_id" => $project->p_manager_id,
                    "manager" => $findManager->name,
                    "manager_email" => $findManager->email,
                    "name" => $project->p_name,
                    "status" => $findStatus->p_status,
                    "deadline" => $project->deadline,
                    "favorite" => $findFavorite
                ];
            }

            if (!empty($success)) {
                return response()->json($success, 200);
            } else {
                throw new Exception("You have no managed projects!");
            }
        }else{
            throw new \Exception('Denied');
        }
    }
    public function leaveProject(Request $request){
        return DB::transaction(function() use(&$request){
            $user=JWTAuth::parseToken()->authenticate();
            $projectId = $request->input('projectId');

            $findUser = Permission::findUserAsParticipant($projectId, $user->id);
            $findUserAsManager = Permission::checkProjectManagerRole($projectId, $user->id);

            if($findUserAsManager === true){
                throw new \Exception("The manager can not leave the project");
            }

            $findAssignedTasks=AssignedTask::where("p_participant_id",$findUser['id'])->get();

            $findAssignedTasks->each->delete();

            $findUser->delete();

            $findProjectInFavorite = FavoriteProjects::where(["added_by"=>$user->id, "project_id"=>$projectId])->get();

            $findProjectInFavorite->each->delete();

            $success="You leave this project";
            return response()->json($success,200);
        });
    }

}
