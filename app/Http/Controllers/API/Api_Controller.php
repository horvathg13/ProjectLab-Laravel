<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AssignedTask;
use App\Models\ProjectParticipants;
use App\Models\Projects;
use App\Models\ProjectsStatus;
use App\Models\Roles;
use App\Models\RoleToUser;
use App\Models\TaskPriorities;
use App\Models\Tasks;
use App\Models\TaskStatus;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GrahamCampbell\ResultType\Success;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use ProjectsTable;
use Tymon\JWTAuth\Facades\JWTAuth;

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

    public function userToRole($id,$role){
        $user = User::find($id);
        $RegisteredRole = Roles::where("role_name", $role)->first();

        if (!$user){
            $success=[   
                "message"=>"Invalid User",
                "code"=>404,
            ];
            return response()->json($success);
        }else if(!$RegisteredRole){
            $success=[   
                "message"=>"Non registered role",
                "code"=>404,
            ];
            return response()->json($success);
        }

        $credentials=[
            'user_id' => $user->id,
            'role_id' => $RegisteredRole->id
        ];

        $role= RoleToUser::create($credentials);
        

        if(!$role){
            $success=[   
                "message"=>"Attached not work",
                "code"=>404,
            ];
            return response()->json($success);
        }else{
            $success=[   
                "message"=>"Thats it!",
                "code"=>200,
            ];
            return response()->json($success);
        }
    }

    public function createProject(Request $request){

        $validator = Validator::make($request->all(),[
            "p_name" => "required",
            "p_manager_id" => "required",
            "date"=> "required|date_format:Y.m.d|after_or_equal:today",
           
        ]);

        if ($validator->fails()){
            $response=[
                "success" => false,
                "message"=> $validator->errors()
            ];
            return response()->json($response, 400);
        }

        $status = ProjectsStatus::where("p_status", "Active")->first();
        
        $formattedDate = Date::createFromFormat('Y.m.d', $request->date)->format('Y-m-d');

        $credentials=[
            "p_name" => $validator->validated()['p_name'],
            "p_manager_id" => $validator->validated()['p_manager_id'],
            "deadline" => $formattedDate,
            "p_status" => $status->id
        ];

        $create = Projects::create($credentials);

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
        
    }

    public function getProjects(){

        $projects = Projects::all();
        $success =[];
  

        foreach($projects as $project){
            $findManager = User::where("id", $project->p_manager_id)->first();
            $findStatus = ProjectsStatus::where("id", $project->p_status)->first();
        

            $success[] =[
                "project_id" => $project->id,
                "manager" => $findManager->name,
                "name"=>$project->p_name,
                "status"=>$findStatus->p_status,
                "deadline"=>$project->deadline
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
            "description" => "required",
            "deadline"=> "required|date_format:Y.m.d|after_or_equal:today",
            "project_id"=> "required",
            "task_priority"=>"required"
           
        ]);

        if ($validator->fails()){
            $response=[
                "success" => false,
                "message"=> $validator->errors()
            ];
            return response()->json($response, 400);
        }

        $uniqueTaskName = Tasks::where("task_name",$validator->validated()['task_name'])->exists();

        if($uniqueTaskName){
            $fail=[
                "message"=> "Name is not unique"
            ];
            return response()->json($fail,500);
        }else{
             $create= Tasks::create([
            "task_name"=>$validator->validated()['task_name'],
            "deadline"=>$validator->validated()['deadline'],
            "description"=>$validator->validated()['description'],
            "p_id"=>$validator->validated()['project_id'],
            "t_status"=>3,
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

        $projects = Projects::where("id", $id)->get();
        $success =[];
  

        foreach($projects as $project){
            $findManager = User::where("id", $project->p_manager_id)->first();
            $findStatus = ProjectsStatus::where("id", $project->p_status)->first();
        

            $success[] =[
                "project_id" => $project->id,
                "manager" => $findManager->name,
                "name"=>$project->p_name,
                "status"=>$findStatus->p_status,
                "deadline"=>$project->deadline
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
       

    

    public function getTasks($id){
        $tasks= Tasks::where("p_id", $id)->get();
        $success=[];

        foreach($tasks as $task){
            $findPriority = TaskPriorities::where("id", $task->t_priority)->first();
            $findStatus = TaskStatus::where("id",$task->t_status)->first();

            $success[]=[
                "task_id"=>$task->id,
                "task_name"=>$task->task_name,
                "dedadline"=>$task->deadline,
                "description"=>$task->description,
                "status"=>$findStatus->task_status,
                "priority"=>$findPriority->task_priority,
            ];
        }

        if(!empty($success)){
            return response()->json($success,200);
        }else{
            $success=[   
                "message"=>"You have no tasks in this project!",
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
            $findStatus = ProjectsStatus::where("id",$p->p_status)->first();
            

            $success[]=[
                "id"=>$p->id,
                "name"=>$findUser->name,
                "email"=>$findUser->email,
                "project_name"=>$p->p_name,
                "status"=>$findStatus->task_status,
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
        $data = $request->json()->all();
        $credentials=[];

        foreach($data as $d){
            AssignedTask::create([
                "task_id"=>$d['task_id'],
                "p_participant_id"=>$d['id']
            ]);
          
        }
 

        $success=[   
            "message"=>"Task attach was successfull!",
            "code"=>200,
            "credentials" =>$credentials
        ];

        return response()->json($success, 200);
    }

    public function AttachMyself($project_id, $task_id, $token){
        $topSecret = env('JWT_SECRET');
        $decodeToken = JWT::decode($token, new Key($topSecret, 'HS256'));
        $userId = $decodeToken->sub;
        $check = [];
        $chekId = null;
        $check = ProjectParticipants::where(["p_id"=> $project_id,
                                            "user_id"=> $userId,
                                            ])->get();

        if($check->count() > 0){
           foreach($check as $c){
            $chekId = $c['id'];
           }
           AssignedTask::create([
                "task_id"=>$task_id,
                "p_participant_id"=>$chekId
            ]);
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

        
        $validator = Validator::make($request->all(),[
            "datas" => "required",
           
        ]);

        if ($validator->fails()){
            $response=[
                "success" => false,
                "message"=> $validator->errors()
            ];
            return response()->json($response, 400);
        }

        $projectDatas = json_decode($validator->validated()['datas'], true);
        $create = false;

        
        foreach($projectDatas as $d){
            $find_status_id = ProjectsStatus::where("p_status", $d["status"])->first();
            $create = ProjectParticipants::create([
                "user_id" => $d["id"],
                "p_id"=>$d["project_id"],
                "p_status"=> $find_status_id->id
                
            ]);
        };
        
        $create= true;
        if(!$create){
            $success=[   
                "message"=>"Fail under create participants",
                "code"=>500,
            ];
            return response()->json($success);
        }else{
            $success=[   
                "message"=>"Thats it! Participants created Successfull",
                "code"=>200,
                
            ];

            return response()->json($success);
        }
    }

    





    

        
    
}
