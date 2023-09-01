<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AssignedTask;
use App\Models\ChatMessages;
use App\Models\ChatView;
use App\Models\FavoriteProjects;
use App\Models\ProjectParticipants;
use App\Models\Projects;
use App\Models\ProjectsStatus;
use App\Models\Roles;
use App\Models\RoleToUser;
use App\Models\TaskPriorities;
use App\Models\Tasks;
use App\Models\TaskStatus;
use App\Models\User;
use ErrorException;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GrahamCampbell\ResultType\Success;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ProjectsTable;
use Symfony\Component\VarDumper\VarDumper;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\Rule;
use function PHPUnit\Framework\isEmpty;
use function PHPUnit\Framework\isNull;
use function PHPUnit\Framework\throwException;

class Api_Controller extends Controller
{
    public function getUserData(){
        try{
            $user = JWTAuth::parseToken()->authenticate();
        } catch(\Exception $ex){
            $user = new \stdClass();
        }
        return response()->json($user); 
    }

    public function getUsers(){
        $users = User::where("status", "active")->get();
        
        $success=[];
            
        foreach($users as $user){
            $roles = $user->roles()->get();
            $roleNames = $roles->pluck('role_name')->implode(', ');

            $success[] = [
                "id" => $user->id,
                "name" => $user->name,
                "email" => $user->email,
                "roles" => $roleNames,
            ];
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
    }
    public function getManagers(){
        $users = User::where("status", "active")->get();
        
        $globalRoles=[];
        $success=[];    
        foreach($users as $user){
            $roles = $user->roles()->get();
            $roleNames = $roles->pluck('role_name');

            $globalRoles[] = [
                "id" => $user->id,
                "name" => $user->name,
                "email" => $user->email,
                "roles" => $roleNames,
            ];
        }
        //var_dump($globalRoles);
        foreach($globalRoles as $global){
            if($global['roles']->contains('Manager') ){
                $success[]=[
                    "id"=>$global['id'],
                    "name"=>$global['name'],
                    "email"=>$global['email'],
                    "roles"=>$global['roles'],
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
    }
    public function getEmployees(Request $request){
        $users = User::where("status", "active")->get();
        $projectId = $request->input('projectId');
        $globalRoles=[];
        $allEmployee=[];
        $success=[];    
        foreach($users as $user){
            $roles = $user->roles()->get();
            $roleNames = $roles->pluck('role_name');

            $globalRoles[] = [
                "id" => $user->id,
                "name" => $user->name,
                "email" => $user->email,
                "roles" => $roleNames,
            ];
        }
        //var_dump($globalRoles);
        foreach($globalRoles as $global){
            if($global['roles']->contains('Employee') ){
                $allEmployee[]=[
                    "id"=>$global['id'],
                    "name"=>$global['name'],
                    "email"=>$global['email'],
                    "roles"=>$global['roles'],
                ];
            }
        }
       
        if(!empty($allEmployee)){
            return response()->json($allEmployee,200);
        }else{
            $fail=[   
                "message"=>"Database error",
                "code"=>404,
            ];
            return response()->json($fail);
        }
    }
    /*public function createRole(Request $request){

        $validator = Validator::make($request->all(),[
            "name" => "required",
            "slug" => "required",
            "permissions" => "required"
        ]);

        if ($validator->fails()){
            $response=[
                "success" => false,
                "message"=> $validator->errors()
            ];
            return response()->json($response, 400);
        }
        

        $role = Sentinel::getRoleRepository()->createModel()->create([
            'name' => $validator->validated()['name'],
            'slug' => $validator->validated()['slug'],
            'permissions'=>$validator->validated()['permissions']
        ]);

        if($role){
            $success=[
                "message" => "Role Created Successfull",
                "status" => 200
            ];
            return response()->json($success);
        }else{
            $success=[
                "message" => "Database Error Occured",
                "status" => 500
            ];

            return response()->json($success);
        }
    }*/

    public function getRoles(){

        $roles = Roles::all();
        if($roles){
            $success=[
            "roles" => $roles,
            "message"=>"That's it!",
            "code"=>200,
            ];
            return response()->json($success);
        }else{
            $success=[   
                "message"=>"Database error",
                "code"=>404,
            ];
            return response()->json($success);
        }
       
    }

    public function userToRole(Request $request){
        $checkUser=JWTAuth::parseToken()->authenticate();
        $getGlobalRoles=[];
        $users = User::where("id", $checkUser->id)->first();
        
        $roles = $users->roles()->get();
        foreach($roles as $role){
            $getGlobalRoles[] = $role->role_name;
               
            
        }

        $haveAdminRole = in_array("Admin",$getGlobalRoles);
        if($haveAdminRole==true){
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
                throw new Exception("Opration canceld!");
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
                        throw new Exception("Role already attached to user!");
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
            throw new Exception("You do not have the correct role for this operation!");
        }
        
    }

    public function createProject(Request $request){
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
            "deadline"=> "required|date_format:Y.m.d",
            "projectId"=>"nullable"
        ];
        $validator = Validator::make($data, $rules);

        if ($validator->fails()){
            $response=[
                "validatorError"=>$validator->errors()->all(),
            ];
            return response()->json($response, 400);
        }

        $getGlobalRoles=[];
        
        $roles = $user->roles()->get();
        foreach($roles as $role){
            $getGlobalRoles[] = $role->role_name;
        }

        $haveAdminRole = in_array("Admin",$getGlobalRoles);
        $haveManagerRole=in_array("Manager",$getGlobalRoles);
        if($haveAdminRole===true || $haveManagerRole === true){
            if(!empty($project_id)){
                $findProject = Projects::where(["id"=>$project_id])->first();
                if($findProject != null){
                    $status = ProjectsStatus::where("p_status", "Active")->first();
                    $formattedDate = Date::createFromFormat('Y.m.d', $request->date)->format('Y-m-d');
                    $findManagerRoleId = Roles::where("role_name", "Manager")->first();
                    $findManagerGlobalRole = RoleToUser::where(["role_id"=>$findManagerRoleId->id, "user_id"=>$managerId])->exists();
                    if($findManagerGlobalRole==true){
                        $update= $findProject->update([
                        "p_name" => $project_name,
                        "p_manager_id" => $managerId,
                        "deadline" => $formattedDate,
                        "p_status" => $status->id

                        ]);
                        $checkManagerIsParticipant= ProjectParticipants::where(["user_id"=>$managerId, "p_id"=>$project_id])->exists();
                        if($checkManagerIsParticipant === false){
                            ProjectParticipants::create([
                                "user_id"=>$request->input('p_manager_id'),
                                "p_id"=>$request->input('p_id'),
                            ]);
                        }
                        $success=[
                            "message"=>"Update Successfull",
                            "data"=>$update,
                        ];

                        return response()->json($success);
                    }else{
                        throw new Exception("User has no manager role in the system!");
                    }
                
                }   
            }else{
                $findManagerRoleId = Roles::where("role_name", "Manager")->first();
                $findManagerGlobalRole = RoleToUser::where(["role_id"=>$findManagerRoleId->id, "user_id"=>$managerId])->exists();
                if($findManagerGlobalRole===true){
                    $status = ProjectsStatus::where("p_status", "Active")->first();
                    
                    $formattedDate = Date::createFromFormat('Y.m.d', $request->date)->format('Y-m-d');

                    $credentials=[
                        "p_name" => $project_name,
                        "p_manager_id" => $managerId,
                        "deadline" => $formattedDate,
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
                    throw new Exception("User has no manager role in the system!");
                }
                
            }     
        }else{
            throw new Exception("Denied!");
        }
        
        
        
    }

    public function getProjects(Request $request){
        /*$today=now();
        $projects = Projects::where("deadline", ">=", $today)->get();*/
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

        $findAdminRole = Roles::where("role_name","Admin")->pluck("id");
        $findUserasAdmin = RoleToUser::where(["user_id"=>$user->id, "role_id"=>$findAdminRole])->exists();
        $success =[];
        if($findUserasAdmin===true){
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
               throw new Exception("You have no attached project!");
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
                throw new Exception("You have no attached project!");
            }
            
        }
       
        
  
        
        
    }

    public function getPriorities(){

        $priorities = TaskPriorities::all();
        $success=[];

        if(!empty($priorities)){

            foreach ($priorities as $p){

                $success[]=[
                    "id"=>$p->id,
                    "name"=>$p->task_priority
                ];
            }

            $responseArray=[
            "message" => "That's all!",
            "data" => $success,
            "status" => 200
        ];
        return response()->json($responseArray,200);
        }else{
            $success=[   
                "message"=>"Database error",
                "code"=>500,
            ];
            return response()->json($success);
        }
        
    }
    public function createTask(Request $request){

        $validator = Validator::make($request->all(),[
            "task_name" => "required",
            "description" => "nullable",
            "deadline"=> "required|date_format:Y-m-d",
            "project_id"=> "required",
            "task_priority"=>"required",
            "task_id"=>"nullable"
           
        ]);
        

        if ($validator->fails()){
            $response=[
                "validatorError"=>$validator->errors()->all(),
            ];
                
           
            return response()->json($response, 400);
        }

       /* $uniqueTaskName = Tasks::where("task_name",$validator->validated()['task_name'])->exists();

        if($uniqueTaskName){
            $fail=[
                "message"=> "Name is not unique"
            ];
            return response()->json($fail,500);*/
        $findTaskStatus=TaskStatus::where('task_status', "Active")->first();
        if($validator->validated()['task_id'] != 0){
            $findTask = Tasks::where(["id"=>$validator->validated()['task_id'], "p_id"=>$validator->validated()['project_id']])->first();
            if($findTask != null){
                $update= $findTask->update([
                    "task_name"=>$validator->validated()['task_name'],
                    "deadline"=>$validator->validated()['deadline'],
                    "description"=>$validator->validated()['description'],
                    "p_id"=>$validator->validated()['project_id'],
                    "t_priority"=>$validator->validated()['task_priority'],

                ]);
                $findTask->save();
                $success=[
                    "message"=>"Update Successfull",
                    "data"=>$update,
                ];
                
                return response()->json($success);
            }    
        }else{
            $create= Tasks::create([
                "task_name"=>$validator->validated()['task_name'],
                "deadline"=>$validator->validated()['deadline'],
                "description"=>$validator->validated()['description'],
                "p_id"=>$validator->validated()['project_id'],
                "t_status"=>$findTaskStatus['id'],
                "t_priority"=>$validator->validated()['task_priority'],

            ]);

            if(!$create){
                $success=[   
                    "message"=>"Fail under create task",
                    "code"=>500,
                ];
                return response()->json($success);
            }else{
                $success=[   
                    "message"=>"Thats it! Task created Successfull",
                    "code"=>200,
                    
                ];

                return response()->json($success);
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
       

    

    public function getTasks(Request $request){
        $user=JWTAuth::parseToken()->authenticate();

        $id = $request->input('projectId');
        $sortData = $request->input('sortData');
        $filterData = $request->input('filterData');
        
        $accessControll=ProjectParticipants::where(["user_id"=>$user->id, "p_id"=>$id])->exists();
        $globalRoles=[];
        $users = User::where("id", $user->id)->get();
        foreach($users as $user){
            $roles = $user->roles()->get();
            $globalRoles = $roles->pluck('role_name');
        }
        $haveAdminRole = $globalRoles->contains('Admin');

        if($accessControll === false && $haveAdminRole === false){
            throw new Exception("Access Denied");
        }

        $data = [
            'projectId' => $id,
            'sortData' => $sortData,
            'filterData'=>$filterData,
        ];
    
        $rules = [
            'projectId' => 'required',
            'sortData'=>'nullable',
            'filterData'=>'nullable',
        ];
    
        $validator = Validator::make($data, $rules);
        
    
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $haveManagerRole=Projects::where(["p_manager_id"=>$user->id, "id"=>$id])->exists();
        $haveParticipantRole = false;


        $taskQuery= Tasks::where("p_id", $id);
        if(!empty($filterData)){
            $ids=[];
            foreach($filterData as $filter){
                if(count($filter) == 1){
                    foreach($filter as $f){
                        $taskQuery->where('t_status', $f['id']);
                    
                    }
                }else{
                    foreach($filter as $f){
                        
                        $ids[]=$f['id'];
                        
                        
                        
                    }
                    if(!empty($ids)){
                        $taskQuery->whereIn('t_status', $ids);
                    }

                }
                
                
            }

        }
        
        if(!empty($sortData)){
            foreach($sortData as $sort){
                $taskQuery->orderBy($sort['key'], $sort['abridgement']);
            }
        }
        $tasks = $taskQuery->get();
        $findUserasParticipant = ProjectParticipants::where(["p_id"=>$id, "user_id" => $user->id])->first();
        if(!empty($findUserasParticipant)){
            $haveParticipantRole = true;
        }
        $success=[];

        foreach($tasks as $task){
            $findPriority = TaskPriorities::where("id", $task->t_priority)->first();
            $findStatus = TaskStatus::where("id",$task->t_status)->first();
            $findTaskParticiantsCount = AssignedTask::where("task_id", $task->id)->count();
            $findMyTask = $findUserasParticipant ? AssignedTask::where(["task_id"=>$task->id,"p_participant_id"=>$findUserasParticipant->id])->exists() : false;
            $success[]=[
                "task_id"=>$task->id,
                "task_name"=>$task->task_name,
                "deadline"=>$task->deadline,
                "description"=>$task->description,
                "status"=>$findStatus->task_status,
                "priority_id"=>$findPriority->id,
                "priority"=>$findPriority->task_priority,
                "employees"=>$findTaskParticiantsCount,
                "mytask"=>$findMyTask,
                "haveManagerRole"=>$haveManagerRole,
                "haveAdminRole"=>$haveAdminRole,
                "haveParticipantRole"=>$haveParticipantRole
            ];
        }

            if(!empty($success)){
                $ids=[];
                return response()->json($success,200);
            }else{
                $ids=[];
                $success=[   
                    "message"=>"You have no tasks in this project!",
                    "haveManagerRole"=>$haveManagerRole,
                    "haveAdminRole"=>$haveAdminRole ?? false,
                    "haveParticipantRole"=>$haveParticipantRole,
                    "code"=>404,
                ];
                return response()->json($success,404);
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
            $success=[   
                "message"=>"You have no participans in this project!",
                "code"=>404,
            ];
            return response()->json($success,404);
        }
       
    }
    public function AssignEmpoyleeToTask(Request $request){
        $user=JWTAuth::parseToken()->authenticate();
        $getGlobalRoles=[];
        $roles = $user->roles()->get();
        foreach($roles as $role){
            $getGlobalRoles[] = $role->role_name;
        }

        $haveManagerRole = in_array("Manager",$getGlobalRoles);
        $haveAdminRole = in_array("Admin",$getGlobalRoles);
        if($haveManagerRole===true || $haveAdminRole===true){
            $data=$request->input('requestData');
            $remove = $request->input('removeData');
            $task_id = $request->input('task_id');
            $project_id = $request->input('project_id');
        
            if(!empty($data)){
                foreach($data as $d){
                    $findAssignedUser = AssignedTask::where(["task_id"=>$d['task_id'], "p_participant_id"=>$d['id']])->exists();
                    if($findAssignedUser==true){
                        throw new Exception("User already assigned to this task");
                    }else{
                        AssignedTask::create([
                            "task_id"=>$d['task_id'],
                            "p_participant_id"=>$d['id']
                        ]);
                    }
                }
            }
            if(!empty($remove)){
                foreach($remove as $r){
                    $findParticipantId = ProjectParticipants::where(["user_id"=>$r['id'],"p_id"=> $project_id])->first();

                    if(empty($findParticipantId)){
                        throw new Exception("Datasbase error: User does not found!");
                    
                    }else{
                        $findAssignedTask = AssignedTask::where([
                            "task_id"=>$task_id,
                            "p_participant_id"=>$findParticipantId['id']
                        ])->first();

                        if(!empty($findAssignedTask)){
                            $findAssignedTask->delete();
                            //$findAssignedTask->save();
                        }else{
                            throw new Exception( "Datasbase error occured!");
                        }
                    }    
                }   
            } 

            $success = ["message"=>"Success!"];
            return response()->json($success,200);
        }else{
            throw new Exception("Denied!");
        }
                    
    }

    public function AttachMyself($project_id, $task_id){
        
        $user = JWTAuth::parseToken()->authenticate();
        $checkUserInProject = ProjectParticipants::where(["p_id"=> $project_id,"user_id"=> $user->id, ])->first();

        if(!empty($checkUserInProject)){
            $alreadyAssigned = AssignedTask::where(["p_participant_id"=> $checkUserInProject->id,"task_id"=> $task_id])->exists();
            if($alreadyAssigned===true){
                throw new Exception("Already attached yourself!");
            }else{
                AssignedTask::create([
                    "task_id"=>$task_id,
                    "p_participant_id"=>$checkUserInProject->id
                ]);
            }
           
            $success = [
                "message"=>"Task attach was successfull!",
                "code"=>200,
            ];

        }else{
            $success = [
                "message"=>"You have no permission to this task!",
                "code"=>500,
            ];
            return response()->json($success, 500);
        }
       
       
        return response()->json($success, 200);
    }

    public function createParticipants(Request $request){
        $user=JWTAuth::parseToken()->authenticate();
        $getGlobalRoles=[];
        $roles = $user->roles()->get();
        foreach($roles as $role){
            $getGlobalRoles[] = $role->role_name;
        }

        $haveManagerRole = in_array("Manager",$getGlobalRoles);
        $haveAdminRole = in_array("Admin",$getGlobalRoles);
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
                throw new Exception("Operation canceld!");
            }
            if(!empty($participants)){
                foreach($participants as $parti){
                    $findParticipants= ProjectParticipants::where(["user_id"=>$parti['id'], "p_id"=>$project['project_id']])->exists();
                    if($findParticipants == true){
                        throw new Exception("Participants already attached!");
                    }else{
                        $find_status_id = ProjectsStatus::where("p_status", $project["status"])->first();
                        ProjectParticipants::create([
                            "user_id"=>$parti['id'],
                            "p_id"=>$project['project_id'],
                        ]);
                    }
                    
                };
                $success=[
                    "message"=>"Thats it! Participants created Successfull",
                    "code"=>200,
                    
                ];
            }
            
            if(!empty($remove)){
                
                foreach($remove as $r){
                    #$r['id'] === ProjectParticipants->id
                    $findManagerInParticipants= ProjectParticipants::where(["id"=>$r['id'],"p_id"=> $project['project_id']])->first();
                    if(empty($findManagerInParticipants)){
                        throw new Exception("Datasbase error: User does not found!");
                    }else{
                        $findAsManager= Projects::where(["p_manager_id"=> $findManagerInParticipants->user_id, "id"=>$project['project_id']])->exists();
                        if($findAsManager === true){
                            throw new Exception("You can not remove the project manager");
                        }else{
                            $findTasks = Tasks::where("p_id",$project['project_id'])->pluck('id');
                            AssignedTask::where("p_participant_id",$r['id'])->whereIn("task_id",$findTasks)->delete();
                            ProjectParticipants::where(["id"=>$r['id'],"p_id"=> $project['project_id']])->delete();
                        }
                    }
                }
                $success=[
                    "message"=>"Thats it!",
                    "code"=>200,
                    
                ];  
            }           

            return response()->json($success,200);
        }else{
            throw new Exception("Denied!");
        }
        
       
    }

    public function getActiveEmployees($task_id){
        $findTask = Tasks::find($task_id);

        if($findTask){
            $participants = $findTask-> projectParticipants()->get();
            $success=[];
            foreach($participants as $p){
                $findUser = User::where("id", $p['user_id'])->first();

                $success[]=[
                    "id"=>$findUser->id,
                    "name"=> $findUser->name,
                    "email"=> $findUser->email,
                ];

               
            }

            return response()->json($success);
            
        }else{
            $success[]=[
                "message"=> "Task does not exists."
            ];

            return response()->json($success);
        }

        
    }


    public function SendMessage(Request $request){

        $participants = $request->input('participants');
        $message= $request->input('message');
        $data = $request->input('data');
        $projectId =$request->input('projectId');
        
        

        if($message !== null && $data !== null){
            $user = JWTAuth::parseToken()->authenticate();

            $create = ChatMessages::create([
                "p_id"=> $projectId,
                "task_id"=>$data['id'],
                "sender_id"=>$user->id,
                "message"=>$message,

            ]);

            $create= true;
            if(!$create){
                throw new Exception("Fail under sending message!");  
                   
            }else{
                $success=[   
                    "message"=>"That's it!",
                    "code"=>200,
                    
                ];

                return response()->json($success);
            }
        }else{
            throw new Exception("Operation canceld");
               
        }
       
    }
    public function getMessages(Request $request){
        
        $user = JWTAuth::parseToken()->authenticate();

        $projectId = $request->input('projectId');
        $taskId = $request->input('taskId');
        $participants=$request->input('participants');
       
        $allMessages=[];
        $findMessages= ChatMessages::where(["p_id" => $projectId,
        "task_id"=> $taskId])->orderBy('created_at', 'asc')->get();
        
        foreach($findMessages as $foum){

        
            $findSender = User::where("id", $foum->sender_id)->first();


            $allMessages[]=[
                
                "sender_id"=>$findSender->id,
                "sender_name"=>$findSender->name,
                
                "message"=>$foum->message,
                "created_at"=>$foum->created_at->format('Y-m-d H:i')
            ];
            
            
        }
        
        
       

        $filterChatMessages=ChatMessages::where(["p_id" => $projectId,
        "task_id"=> $taskId])->where("sender_id","!=",$user->id)->get();
        foreach($filterChatMessages as $message){
            $findChatView = ChatView::where("chat_id", $message->id)->first();
            if(empty($findChatView)){
                ChatView::create([
                "chat_id" => $message->id,
                ]);
            }else{
                $findChatView->touch();
                $findChatView->update([
                    "chat_id" => $message->id,
                ]);
            }

        }
       
        
       
        $success=[
            "messageData"=> $allMessages,
            "currentUser_id"=> $user->id,
            "message"=>"Message Query was Successfull!"
        ];
        
        return response()->json($success, 200);
        

    }
    
    public function getUnreadMessages(){
        $user = JWTAuth::parseToken()->authenticate();
        $haveUnreadProjectMessages=false;
        $haveUnreadOpenedProjectMessages=false;
        $haveUnreadOpenedTaskMessages=false;
        $haveUnreadTaskMessages=false;
        $Project=[];
        $Task=[];
        $findProjects = ProjectParticipants::where("user_id", $user->id)->get();
        $enterTheHook=false;
        foreach($findProjects as $projects){
          
            $findChatMessageByProjectId = ChatMessages::where("p_id", $projects['p_id'])->where("task_id", null)->where("sender_id","!=", $user->id)->get();
            
            foreach($findChatMessageByProjectId as $findByProjectId){
                $existsInChatView = ChatView::where("chat_id", $findByProjectId['id'])->exists();
                if($existsInChatView==false){
                    $haveUnreadProjectMessages=true;
                    $Project[]=[
                        "UnreadProject_Project_id"=>$findByProjectId['p_id'],
                    ];
                }/*else{
                    //ezt az else ágat át kellene gondolni, mert most minden üzenet új id-val szúródik be az
                    //adatbázisba. Így nem lesz olyan eset, amikor ugyanaz az id mégegyszer szerepelne. 
                    $findOpenedMessage= ChatView::where("chat_id",$findByProjectId['id'])->get();
               
                    
                    foreach($findOpenedMessage as $opened){
                        if($opened['updated_at']<=$findByProjectId['created_at']){
                            $haveUnreadOpenedProjectMessages=true;
                            $Project[]=[
                                "UnreadOpenedProject_Project_id"=>$findByProjectId['p_id'],
                            ];
                        }
                    }
                }*/
            }
            
            $assignedTasks = AssignedTask::where("p_participant_id",$projects['id'] )->get();
            

            foreach($assignedTasks as $findByTaskId){
                
                $findChatMessageByTaskId=ChatMessages::where("p_id", $projects['p_id'])->where("task_id", $findByTaskId['task_id'])->where("sender_id","!=", $user->id)->get();
                foreach($findChatMessageByTaskId as $ChatMessage){
                    $existsInChatView = ChatView::where("chat_id", $ChatMessage['id'])->exists();
                    if($existsInChatView==false){
                        $haveUnreadTaskMessages=true;
                        $Task[]=[
                            "UnreadTaskMessages_Task_id"=>$ChatMessage['task_id'],
                            "UnreadTask_Project_id"=>$ChatMessage['p_id']
                        ];
                    }/*else{
                        //szerintem itt is u.a. mint fent...
                        $findOpenedMessage= ChatView::where("chat_id",$ChatMessage['id'])->get();

                        $enterTheHook=true;
                        foreach($findOpenedMessage as $opened){
                            if($findByTaskId['created_at']>$opened['updated_at']){
                                $haveUnreadOpenedTaskMessages=true;
                                $Task[]=[
                                    "UnreadOpenedTask_Task_id"=>$ChatMessage['task_id'],
                                    "UnreadOpenedTask_Project_id"=>$ChatMessage['p_id']
                                ];
                            }
                        }
                    }*/
                }
            }
        }

        $findManagedProjects= Projects::where(["p_manager_id"=>$user->id])->pluck('id');

        if($findManagedProjects->isNotEmpty()){
            
            $findTasks = Tasks::whereIn("p_id",$findManagedProjects)->get();
            foreach($findTasks as $task){
                $findChatMessageByTaskId=ChatMessages::where("p_id", $task['p_id'])->where("task_id", $task['id'])->where("sender_id","!=", $user->id)->get();
                foreach($findChatMessageByTaskId as $ChatMessage){
                    $existsInChatView = ChatView::where("chat_id", $ChatMessage['id'])->exists();
                    if($existsInChatView==false){
                        $haveUnreadTaskMessages=true;
                        $Task[]=[
                            "UnreadTaskMessages_Task_id"=>$ChatMessage['task_id'],
                            "UnreadTask_Project_id"=>$ChatMessage['p_id']
                        ];
                    }
                }
            }
        }

        $success=[
            "unreadProjectMessages"=>$haveUnreadProjectMessages,
            "unreadOpenedProjectMessage"=>$haveUnreadOpenedProjectMessages,
            "Project"=>$Project,
            "unreadTaskMessages"=>$haveUnreadTaskMessages,
            "unreadOpenedTaskMessages"=>$haveUnreadOpenedTaskMessages,
            "Task"=>$Task,
            "enterTheHook"=>$enterTheHook

        ];
        return response()->json($success,200);

    }

    public function getProjectandTaskButtons($ProjectId){
        $user= JWTAuth::parseToken()->authenticate();
        $json_data=Storage::get("buttons/buttons.json");
        $buttons = json_decode($json_data, true);
        $getGlobalRoles = [];
        $haveProjectManagerRole = false;
        $haveProjectParticipantRole = false;
        $haveAdminRole = false;
        $ManagerButtons = $buttons["manager"];
        $EmployeeButtons = $buttons["employee"];
        $AdminButtons = $buttons["admin"];
        $messages=[];
        $success=[];
        $superUser=[];

        $users = User::where("id", $user->id)->get();
            
        foreach($users as $user){
            $roles = $user->roles()->get();
            $globalRoles = $roles->pluck('role_name');

            
            $getGlobalRoles[] = [
               "roles" => $globalRoles,
            ];
        }
       
        $getProject = Projects::where("id", $ProjectId)->first();
        $haveProjectManagerRole = $globalRoles->contains('Manager');
        $haveAdminRole = $globalRoles->contains('Admin');

        if($getProject["p_manager_id"] == $user->id && $haveProjectManagerRole == true){
            
                
            $haveProjectManagerRole = true;
            $haveProjectParticipantRole = true;
            
            $success[]=[
                "manager"=>$ManagerButtons,
                "employee"=>$EmployeeButtons,
                "message"=> "Welcome, Manager!"
            ];
                
            
            
        }else{
            $getParticipantRole = ProjectParticipants::where(["user_id"=> $user->id, "p_id"=>$ProjectId])->exists();
            if($getParticipantRole==true){
                $haveProjectParticipantRole = true;
                $success[]=[
                    "employee"=> $EmployeeButtons,
                    "message"=> "You can access!"
                ];
            }else if($haveAdminRole===false){
                
                throw new Exception("You have no access permission!");
                
            }
        }

        
        if (
            $haveAdminRole ==true && 
            $haveProjectParticipantRole ==true &&  
            $haveProjectManagerRole ==true
            ){
            
            $superUser[]=[
                "employee"=>$EmployeeButtons,
                "manager"=>$ManagerButtons
            ];

            return response()->json($superUser,200);    
        }else if(($haveAdminRole == true && $haveProjectParticipantRole ==false) ||($haveAdminRole == true && $haveProjectParticipantRole ==true)){
            
            $haveAdminRole = true;
            
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
        $getGlobalRoles=[];
        $success=[];

        $users = User::where("id", $user->id)->get();
            
        foreach($users as $user){
            $roles = $user->roles()->get();
            $globalRoles = $roles->pluck('role_name');

            
            $getGlobalRoles[] = [
               "roles" => $globalRoles,
            ];
        }

        $haveAdminRole = $globalRoles->contains('Admin');

        if($haveAdminRole == true){
            $success[]=[
                "admin"=>$AdminButtons,
                "message"=>"You can access to admin buttons!"
            ];
        }else{
            
            throw new Exception("Access denid");
        }

        return response()->json($success,200);
    }

    public function getStatus($ProjectId, $TaskId){
        $data = [
            'ProjectId' => $ProjectId,
            'TaskId' => $TaskId,
        ];
    
        $rules = [
            'ProjectId' => 'nullable',
            'TaskId' => 'nullable',
        ];
    
        $validator = Validator::make($data, $rules);
    
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        $success=[];

        if($TaskId=='null'){
            $getProjectsStatus = ProjectsStatus::all();
            $success[]=[
                "message"=>"That's it!",
                "status"=>$getProjectsStatus
            ];
            return response()->json($success,200);
        }else{
            $getTasksStatus = TaskStatus::all();
            $success[]=[
                "message"=>"That's it!",
                "status"=>$getTasksStatus
            ];
            return response()->json($success,200);
        }
    }

    
    public function setStatus(Request $request){

        $ProjectId=$request->input('projectId');
        $TaskId=$request->input('taskId');
        $StatusId=$request->input('StatusId');
        $PriorityId=$request->input('priorityId');
        $SetAllTask=$request->input('setAllTask');
        $SetAllPriority=$request->input('setAllPriority');

        $data = [
            'ProjectId' => $ProjectId,
            'TaskId' =>  $TaskId,
            'StatusId'=>$StatusId,
            'PriorityId'=>$PriorityId,
            'SetAllTask'=> $SetAllTask,
            'SetAllPriority'=> $SetAllPriority,
        ];
    
        $rules = [
            'ProjectId' => 'required',
            'TaskId' => 'nullable',
            'StatusId' => 'nullable',
            'PriorityId'=>'nullable',
            'SetAllTask'=>'nullable|boolean',
            'SetAllPriority'=>'nullable|boolean'
        ];
    
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        if($StatusId == 'undefined'){
            throw new Exception("Status set is reqired!");
        }

        $SetAllTaskBool = filter_var($SetAllTask,FILTER_VALIDATE_BOOLEAN);
        $SetAllPriorityBool = filter_var($SetAllPriority,FILTER_VALIDATE_BOOLEAN);
        $success=[];

        if($TaskId == null){
            $findProject= Projects::where("id", $ProjectId)->first();
            if(!empty($findProject)){
                

                $findProject->touch();
                $findProject->update([
                    "p_status"=>$StatusId
                ]);
                
               
                $success[]=[
                    "message"=>"Update Successfull!",
                ];
                return response()->json($success,200);
            }

        }else{
            
            $findProjectTasks = Tasks::where(["p_id"=>$ProjectId, "id"=>$TaskId])->first();
            if($StatusId !== null && $SetAllTaskBool===false){
                
                $findProjectTasks->touch();
                $findProjectTasks->update([
                    "t_status"=>$StatusId,
                    
                ]);
                $success[]=[
                    "message"=>"Update Successfull!",
                ];
            }else if($StatusId !== null && $SetAllTaskBool===true){
                
                $findProjectTasks = Tasks::where("p_id", $ProjectId)->get();
                if($findProjectTasks->isEmpty()){
                    throw new Exception("Query is empty");
                }else{
                    foreach($findProjectTasks as $task){
                        $task->touch();
                        $task->t_status = $StatusId;
                        $task->save();
                    }
                }
                
            }
            
            if($PriorityId !== null && $SetAllPriorityBool===true){
                $findProjectTasks = Tasks::where("p_id", $ProjectId)->get();
                if(empty($findProjectTasks)){
                    throw new Exception("Query is empty");
                }else{
                    foreach($findProjectTasks as $task){
                        $task->touch();
                        $task->update([
                            "t_priority"=>$PriorityId
                        ]);
                    }
                    
                } 
                return response()->json($findProjectTasks,200);
            }else if($PriorityId !== null && $SetAllPriorityBool===false){
                $findTask = Tasks::where(["p_id"=> $ProjectId, "id"=>$TaskId])->first();
                if(empty($findTask)){
                    throw new Exception("Task does not exists");
                }else{
                    
                    $findTask->touch();
                    $findTask->update([
                        "t_priority"=>$PriorityId
                    ]);
                  
                    
                } 
                return response()->json($findTask,200);
            }
           

           
            $success[]=[
                "message"=>"Update Successfull!",
            ];
            return response()->json($success,200);
            
            
            
            
        
        }
    }

    public function statusFilterProjectOrTask($ProjectId,$Task, $StatusId){
        $data = [
            'ProjectId' => $ProjectId,
            'Task' => $Task,
            'StatusId'=>$StatusId,
        ];
    
        $rules = [
            'ProjectId' => 'nullable',
            'Task' => 'nullable',
            'StatusId'=>'required',
        ];
    
        $validator = Validator::make($data, $rules);
    
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
       
        
        $success=[];
        if($Task == 'null' && $ProjectId == 'null'){
            $findProjects= Projects::where("p_status",$StatusId)->get();
            if($findProjects->isNotEmpty()){
                foreach($findProjects as $project){
                    $findManager = User::where("id", $project->p_manager_id)->first();
                    $findProjectsStatus = ProjectsStatus::where("id", $StatusId)->first();
                
        
                    $success[] =[
                        "project_id" => $project->id,
                        "manager_id"=>$project->p_manager_id,
                        "manager" => $findManager->name,
                        "manager_email"=>$findManager->email,
                        "name"=>$project->p_name,
                        "status"=>$findProjectsStatus->p_status,
                        "deadline"=>$project->deadline
                    ];
    
                }
              
                return response()->json($success,200);
            }else{
                
                throw new Exception("Project does not exists!");
            }

        }else{
           
            $findProjectTasks = Tasks::where(["p_id"=>$ProjectId, "t_status"=>$StatusId])->get();
            if($findProjectTasks->isNotEmpty()){
                foreach($findProjectTasks as $task){
                    $findPriority = TaskPriorities::where("id", $task->t_priority)->first();
                    $findStatus = TaskStatus::where("id",$task->t_status)->first();

                    $success[]=[
                        "task_id"=>$task->id,
                        "task_name"=>$task->task_name,
                        "deadline"=>$task->deadline,
                        "description"=>$task->description,
                        "status"=>$findStatus->task_status,
                        "priority_id"=>$findPriority->id,
                        "priority"=>$findPriority->task_priority,
                    ];
                }
            }else{
                throw new Exception("Task does not exists!");
            }
            

            if(!empty($success)){
                return response()->json($success,200);
            }else{
                $success[]=[   
                    "message"=>"You have no tasks in this project!",
                    "code"=>404,
                ];
                return response()->json($success,404);
            }
           
            
            
        }
    }

    public function Notifications(){
        $user= JWTAuth::parseToken()->authenticate();
        $success = [];
        $urgentDay = date("Y-m-d", strtotime("+5 days"));
        $findActive=ProjectsStatus::where("p_status","Active")->first();
        $findActiveProjects=Projects::where("p_status",$findActive->id)->pluck('id');
        $findMyProjects = ProjectParticipants::where('user_id',$user->id)->whereIn("p_id",$findActiveProjects)->get();

        foreach($findMyProjects as $myp){
            $findChatMessages=ChatMessages::where(['p_id'=>$myp['p_id'], 'task_id'=>null])->where('sender_id', '!=', $user->id)->get();
            foreach($findChatMessages as $message){
                $findReadChatMessages = ChatView::where('chat_id',$message['id'])->exists();
                if($findReadChatMessages === false){
                    $findProject = Projects::where('id',$myp['p_id'])->first();
                    $success[]=[
                        "id"=>$findProject->id,
                        "type"=>"Project",
                        "title"=>$findProject->p_name,
                        "status"=>$findActive['p_status'],
                        "deadline"=>$findProject->deadline,
                    ];
                }
            }
        }

        $findUserasProjectManager= Projects::where(["p_manager_id" =>$user->id, "p_status"=>$findActive['id'] ])->where("deadline", "<=", $urgentDay)->get();

        if($findUserasProjectManager->isNotEmpty()){
            
            foreach($findUserasProjectManager as $manager){
                $success[]=[
                    "id"=>$manager->id,
                    "type"=>"Project",
                    "title"=>$manager->p_name,
                    "status"=>$findActive['p_status'],
                    "deadline"=>$manager->deadline,
                ];
            }
        }

        $findUserasParticipant=ProjectParticipants::where("user_id", $user->id)->pluck('id');
        $findAssignedTask=AssignedTask::whereIn("p_participant_id", $findUserasParticipant)->get();

        foreach($findAssignedTask as $task){
            $findTask=Tasks::where("id",$task['task_id'])->where("deadline", "<=", $urgentDay)->get();
           
            if( $findTask->isNotEmpty()){
                foreach($findTask as $t){
                    
                    $findTaskStatus=TaskStatus::where("id", $t->t_status)->first();

                    $success[]=[
                        "id"=>$t->id,
                        "type"=>"Task",
                        "title"=>$t->task_name,
                        "status"=>$findTaskStatus['task_status'],
                        "deadline"=>$t->deadline,
                    ];
                }
            }

            $findChatMessages=ChatMessages::where('task_id',$task['task_id'])->where('sender_id', '!=', $user->id)->pluck('id');
            
            $findUnreadChatMessages = ChatView::whereIn('chat_id',$findChatMessages)->exists();
            if($findUnreadChatMessages === false){
                $findMyTask=Tasks::where("id",$task['task_id'])->get();
                foreach($findMyTask as $mytask){
                    $findTaskStatus=TaskStatus::where("id", $mytask['t_status'])->first();
                    $success[]=[
                        "id"=>$mytask['id'],
                        "type"=>"Task",
                        "title"=>$mytask['task_name'],
                        "status"=>$findTaskStatus->task_status,
                        'deadline'=>$mytask['deadline'],
                    ];
                }
            }
        }
        
        $success_unique= [];
        foreach ($success as $item) {
            if (!in_array($item['id'], array_column($success_unique, 'id'))) {
                $success_unique[] = $item;
            }
        }

        return response()->json($success_unique, 200);
    }

    public function Completed(Request $request){
        $user= JWTAuth::parseToken()->authenticate();
        $ProjectId = $request->input('projectId');
        $TaskData = $request->input('taskData');

        $data = [
            'ProjectId' => $ProjectId,
            'taskData' => $TaskData,
        ];
    
        $rules = [
            'ProjectId' => 'nullable',
            'taskData'=>'required',
        ];
    
        $validator = Validator::make($data, $rules);
        
    
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $success=[];

        $findUserasParticipant=ProjectParticipants::where(["user_id"=> $user->id, "p_id"=>$ProjectId])->first();
        if(empty($findUserasParticipant)){
            throw new Exception("Denied!");
        }else{
                $findAssignedTask=AssignedTask::where(["p_participant_id" => $findUserasParticipant->id, "task_id"=>$TaskData['task_id']?? $TaskData['id']])->first();
                if(empty($findAssignedTask)){
                    throw new Exception("Denied!");
                    
                }else{
                    
                    $findTask=Tasks::where("id", $TaskData['task_id']?? $TaskData['id'])->first();
                    $findStatus = TaskStatus::where("task_status", $TaskData['status'])->first();
                    if(!empty($findTask)){
                        
                        $findTask->update([
                            "t_status"=>$findStatus->id
                        ]);
                        $findTask->save();
                        
                    }else{
                        throw new Exception("Task does not exist!");
                    }

                    
                }
                    
            
            
        }

        $success[]=[
            "message"=>"Nice job!"
        ];

        return response()->json($success,200);
    }

    public function MyTasks(Request $request){

        $jwt = JWTAuth::parseToken();
        $user = $jwt->authenticate();
        $sortData = $request->input('sortData');
        
        $success = [];
        $findProjectStatus = ProjectsStatus::where("p_status", "Active")->first();
        $findActiveProjects = Projects::where("p_status", $findProjectStatus->id)->pluck('id');
        $findUserasParticipant = ProjectParticipants::where("user_id",$user->id)->whereIn("p_id",$findActiveProjects)->pluck('id');
        
        $findStatus = TaskStatus::whereIn('task_status', ['Active', 'Completed'])->pluck('id');
        $findTasks = null;
        
        $findAssignedTask = AssignedTask::whereIn("p_participant_id", $findUserasParticipant)->pluck('task_id');
        
        $findTasksQuery = Tasks::whereIn('id', $findAssignedTask)->whereIn('t_status', $findStatus);
            

        if (!empty($sortData)) {
            foreach ($sortData as $sort) {
                $findTasksQuery->orderBy($sort['key'], $sort['abridgement']);
            }
        }

        $findTasks = $findTasksQuery->get();
        
        foreach ($findTasks as $task) {
            $findPriority = TaskPriorities::where("id", $task->t_priority)->first();
            $findProjectname = Projects::where("id", $task->p_id)->first();

            $status = TaskStatus::where('id', $task['t_status'])->first();

            $success[] = [
                "id" => $task->id,
                "name" => $task->task_name,
                "deadline" => $task->deadline,
                "description" => $task->description,
                "projectName" => $findProjectname['p_name'],
                "projectId" => $findProjectname['id'],
                "status" => $status['task_status'],
                "priority" => $findPriority->task_priority,
                "priorityId" => $findPriority->id
            ];
            
        }
        

        if (empty($success)) {
            throw new Exception("You have no tasks!");
        }
       
        return response()->json($success, 200);
    }
    
    public function getUserRole(){
        $user= JWTAuth::parseToken()->authenticate();
        
        $success=[];
            
        
        $roles = $user->roles()->get();
        foreach($roles as $r){
            $success[]=["role"=>$r->role_name];
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
    }
    
    public function Sort(Request $request){
        $selectedSortId = $request->input('type');
        $sortKey = $request->input('key');
        $sortingArray = $request->input('data');

        $data = [
            'type' => $selectedSortId,
            'key' => $sortKey,
            'array'=>$sortingArray
        ];
    
        $rules = [
            'type' => 'required',
            'key'=>'required',
            'array'=>'required',
        ];
    
        $validator = Validator::make($data, $rules);
        
    
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $success=[];
        if($selectedSortId== 1){
            $success = collect($sortingArray)->sortBy($sortKey)->values()->all();

        }else{
            $success = collect($sortingArray)->sortByDesc($sortKey)->values()->all();
        }

        return response()->json($success,200);

    }     
    
    public function addFavoriteProject(Request $request){
        $user= JWTAuth::parseToken()->authenticate();
        $projectData = $request->input('project');

        $findProjectInFavorite = FavoriteProjects::where(["added_by"=>$user->id, "project_id"=>$projectData['project_id']])->exists();
        if($findProjectInFavorite === false){
            FavoriteProjects::create([
                "added_by"=>$user->id,
                "project_id"=>$projectData['project_id']
            ]);
        }else{
            throw new Exception("Project already added to your favorites!");
        }

        $success=[
            "message"=>"That's your favorite!"
        ];

        return response()->json($success,200);
    }

    public function removeFromFavorite(Request $request){
        $user= JWTAuth::parseToken()->authenticate();
        $projectData = $request->input('project');

        $findProjectInFavorite = FavoriteProjects::where(["added_by"=>$user->id, "project_id"=>$projectData['project_id']])->exists();
        if($findProjectInFavorite == true){
            FavoriteProjects::where(["added_by"=>$user->id, "project_id"=>$projectData['project_id']])->delete();
        }else{
            throw new Exception("Project does not exists in your favorites!");
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
        
        /*$projectsQuery= Projects::query();
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
        $projects = $projectsQuery->get();*/
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
                $findFavorite = FavoriteProjects::where(["added_by"=> $user->id, "project_id"=>$project->id])->exists();
    
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

        $findActiveProjects=ProjectsStatus::where("p_status","Active")->first();
        $projectsQuery= Projects::where(["p_manager_id"=> $user->id, "p_status"=>$findActiveProjects->id]);

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

        $success =[];
  
       
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
                "favorite"=>$findFavorite
            ];

           
        }

        if(!empty($success)){
            return response()->json($success,200);
        }else{
            throw new Exception("You have no managed projects!");
           
        }
    }

    public function getManagerTasks(Request $request){
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
        $getGlobalRoles=[];
        $users = User::where("id", $user->id)->get();
        foreach($users as $user){
            $roles = $user->roles()->get();
            $globalRoles = $roles->pluck('role_name');

            
            $getGlobalRoles[] = [
               "roles" => $globalRoles,
            ];
        }

        $haveManagerRole = $globalRoles->contains('Manager');
        if($haveManagerRole===true){
            $findActiveProjects=ProjectsStatus::where("p_status","Active")->first();
            $projectsQuery= Projects::where(["p_manager_id"=> $user->id, "p_status"=>$findActiveProjects->id])->pluck('id');
            $taskQuery = Tasks::whereIn("p_id",$projectsQuery);
                if(!empty($filterData)){
                    $ids=[];
                    foreach($filterData as $filter){
                        if(count($filter) == 1){
                            foreach($filter as $f){
                                $taskQuery->where('t_status', $f['id']);
                            }
                        }else{
                            foreach($filter as $f){
                                
                                $ids[]=$f['id'];
                                
                                
                                
                            }
                            if(!empty($ids)){
                                $taskQuery->whereIn('t_status', $ids);
                            }

                        }
                        
                        
                    }

                }
                
                if(!empty($sortData)){
                    foreach($sortData as $sort){
                        $taskQuery->orderBy($sort['key'], $sort['abridgement']);
                    }
                }
                $tasks = $taskQuery->get();
                
                $success=[];

                foreach($tasks as $task){
                    $findPriority = TaskPriorities::where("id", $task->t_priority)->first();
                    $findStatus = TaskStatus::where("id",$task->t_status)->first();
                    $findTaskParticiantsCount = AssignedTask::where("task_id", $task->id)->count();

                    $success[]=[
                        "task_id"=>$task->id,
                        "task_name"=>$task->task_name,
                        "deadline"=>$task->deadline,
                        "description"=>$task->description,
                        "status"=>$findStatus->task_status,
                        "priority_id"=>$findPriority->id,
                        "priority"=>$findPriority->task_priority,
                        "employees"=>$findTaskParticiantsCount,
                        "haveManagerRole"=>$haveManagerRole,
                        "p_id"=>$task->p_id
                    ];
                }

                    if(!empty($success)){
                        $ids=[];
                        return response()->json($success,200);
                    }else{
                        throw new Exception("You have no managed tasks!");
                        
                    }
        }else{
            throw new Exception("Access Denied");
        }

        
    }

    public function managedCompletedTasks(){
        $user=JWTAuth::parseToken()->authenticate();
        $users = User::where("id", $user->id)->get();
       
        $getGlobalRoles=[];
        foreach($users as $user){
            $roles = $user->roles()->get();
            $globalRoles = $roles->pluck('role_name');

            
            $getGlobalRoles[] = [
               "roles" => $globalRoles,
            ];
        }

        $haveManagerRole = $globalRoles->contains('Manager');
        $findCompleted = TaskStatus::where("task_status", "Completed")->pluck('id');
        if($haveManagerRole===true){
            $findActiveProjects=ProjectsStatus::where("p_status","Active")->first();
            $projectsQuery= Projects::where(["p_manager_id"=> $user->id, "p_status"=>$findActiveProjects->id])->pluck('id');
            $taskQuery = Tasks::whereIn("p_id",$projectsQuery);
              
                    
                       
            $taskQuery->where('t_status', $findCompleted);
            $taskQuery->orderBy("deadline", "asc");               
            $tasks = $taskQuery->get();            
                        
            $success=[];

            foreach($tasks as $task){
                $findPriority = TaskPriorities::where("id", $task->t_priority)->first();
                $findStatus = TaskStatus::where("id",$task->t_status)->first();
                $findProject = Projects::where("id", $task->p_id)->value("p_name");
                $findTaskParticiantsCount = AssignedTask::where("task_id", $task->id)->count();
                $success[]=[
                    "p_id"=>$task->p_id,
                    "p_name"=>$findProject,
                    "task_id"=>$task->id,
                    "task_name"=>$task->task_name,
                    "deadline"=>$task->deadline,
                    "description"=>$task->description,
                    "status"=>$findStatus->task_status,
                    "priority_id"=>$findPriority->id,
                    "priority"=>$findPriority->task_priority,
                    "employees"=>$findTaskParticiantsCount,
                    "haveManagerRole"=>$haveManagerRole,
                ];
            }

            if(!empty($success)){
                
                return response()->json($success,200);
            }else{
                
                $success=[   
                    "message"=>"You have no tasks to be approved",
                    "haveManagerRole"=>$haveManagerRole,
                    "code"=>404,
                ];
                return response()->json($success,404);
            }
        }else{
            throw new Exception("Access Denied");
        }        
    }

    public function getManagerNotification(){
        $user=JWTAuth::parseToken()->authenticate();
        $users = User::where("id", $user->id)->get();
       
        $getGlobalRoles=[];
        foreach($users as $user){
            $roles = $user->roles()->get();
            $globalRoles = $roles->pluck('role_name');

            
            $getGlobalRoles[] = [
               "roles" => $globalRoles,
            ];
        }

        $haveManagerRole = $globalRoles->contains('Manager');
        if($haveManagerRole===true){
            $findActiveProjects=ProjectsStatus::where("p_status","Active")->first();
            $findTasksStatus = TaskStatus::where("task_status", "Completed")->first();
            $projectsQuery= Projects::where(["p_manager_id"=> $user->id, "p_status"=>$findActiveProjects->id])->pluck('id');
            $taskQuery = Tasks::whereIn("p_id",$projectsQuery)->where("t_status",$findTasksStatus->id);
               
            $tasks = $taskQuery->count();
            
            
            return response()->json($tasks,200);
                    
        }

    }

    public function acceptAllTask(){
        $user=JWTAuth::parseToken()->authenticate();
        $users = User::where("id", $user->id)->get();
       
        $getGlobalRoles=[];
        foreach($users as $user){
            $roles = $user->roles()->get();
            $globalRoles = $roles->pluck('role_name');

            
            $getGlobalRoles[] = [
               "roles" => $globalRoles,
            ];
        }

        $haveManagerRole = $globalRoles->contains('Manager');

        if($haveManagerRole===true){
            $findCompleted = TaskStatus::where("task_status", "Completed")->pluck('id');
            $findActiveProjects=ProjectsStatus::where("p_status","Active")->first();
            $projectsQuery= Projects::where(["p_manager_id"=> $user->id, "p_status"=>$findActiveProjects->id])->pluck('id');
            $taskQuery = Tasks::whereIn("p_id",$projectsQuery);
            $taskQuery->where('t_status', $findCompleted);
            $tasks = $taskQuery->get();            
                        
            $findAccepted = TaskStatus::where("task_status", "Accepted")->first();
            foreach($tasks as $task){
                $task->update([
                   "t_status"=>$findAccepted->id
                ]);
            }

           
            $success=[   
                "message"=>"All task accepted!",
                "haveManagerRole"=>$haveManagerRole,
                "code"=>200,
            ];
            return response()->json($success,200);
            
        }else{
            throw new Exception("Access Denied");
        }        

    }

    public function leaveProject(Request $request){
        $user=JWTAuth::parseToken()->authenticate();

        $projectId = $request->input('projectId');
        
        $findUser=ProjectParticipants::where(["user_id"=>$user->id, "p_id"=>$projectId])->first();

        $findUserAsManager=Projects::where(["id"=>$projectId, "p_manager_id"=>$user->id])->exists();
        if($findUserAsManager === true){
            throw new Exception("The manager can not leave the project");
        }
        
        $findAssignedTasks=AssignedTask::where("p_participant_id",$findUser['id'])->get();
        
        $findAssignedTasks->each->delete();
        
        $findUser->delete();

        $findProjectInFavorite = FavoriteProjects::where(["added_by"=>$user->id, "project_id"=>$projectId])->get();
        
        $findProjectInFavorite->each->delete();
            //FavoriteProjects::where(["added_by"=>$user->id, "project_id"=>$projectId])->delete();
        $success="You leave this project";
        return response()->json($success,200);
    }

    public function countOfMyTasks(){
        $user=JWTAuth::parseToken()->authenticate();

        $findProjectStatus = ProjectsStatus::where("p_status", "Active")->first();
        $findActiveProjects= Projects::where("p_status", $findProjectStatus->id)->pluck('id');
        $findUserasParticipant = ProjectParticipants::where("user_id",$user->id)->whereIn("p_id",$findActiveProjects)->pluck('id');
        $findStatus = TaskStatus::where('task_status', 'Active')->pluck('id');
        $findTasks = null;
        
        $findAssignedTask = AssignedTask::whereIn("p_participant_id", $findUserasParticipant)->pluck('task_id');
        $findTasksQuery = Tasks::whereIn('id', $findAssignedTask)->where('t_status', $findStatus);
       
        $findTasks = $findTasksQuery->count();
        
        if ($findTasks<0) {
            return response(0);
        }else{
            return response()->json($findTasks, 200);
        }
       
        
    }
    public function saveProfileData(Request $request){
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
            throw new Exception('User does not exitst');
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

         
    }

    public function AccessControllForTasks(Request $request){
        $user= JWTAuth::parseToken()->authenticate();
        $ProjectId = $request->input("p_id");
        $findUserasParticipant=ProjectParticipants::where(["user_id"=> $user->id, "p_id"=>$ProjectId])->exists();
        $globalRoles=[];
       
        $roles = $user->roles()->get();
        foreach($roles as $r){
            $globalRoles= $roles->pluck('role_name');
        }
        $haveAdminRole = $globalRoles->contains('Admin');
        if($findUserasParticipant === false && $haveAdminRole === false){
            throw new Exception("Access Denied");
        }else{
            return response(200);
        }
    }
}
