<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AssignedTask;
use App\Models\ChatMessages;
use App\Models\ChatView;
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
use ProjectsTable;
use Symfony\Component\VarDumper\VarDumper;
use Tymon\JWTAuth\Facades\JWTAuth;

use function PHPUnit\Framework\isEmpty;
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
            "p_id"=>"nullable"
           
        ]);

        if ($validator->fails()){
            $response=[
                "success" => false,
                "message"=> $validator->errors()
            ];
            return response()->json($response, 400);
        }
        if($validator->validated()['p_id'] != 0){
            $findProject = Projects::where(["id"=>$validator->validated()['p_id']])->first();
            if($findProject != null){
                $status = ProjectsStatus::where("p_status", "Active")->first();
                $formattedDate = Date::createFromFormat('Y.m.d', $request->date)->format('Y-m-d');

                $update= $findProject->update([
                    "p_name" => $validator->validated()['p_name'],
                    "p_manager_id" => $validator->validated()['p_manager_id'],
                    "deadline" => $formattedDate,
                    "p_status" => $status->id

                ]);
                $success=[
                    "message"=>"Update Successfull",
                    "data"=>$update,
                ];

                 return response()->json($success);
            }   
        }else{
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
        
        
    }

    public function getProjects(){

        $projects = Projects::all();
        $success =[];
  

        foreach($projects as $project){
            $findManager = User::where("id", $project->p_manager_id)->first();
            $findStatus = ProjectsStatus::where("id", $project->p_status)->first();
        

            $success[] =[
                "project_id" => $project->id,
                "manager_id"=>$project->p_manager_id,
                "manager" => $findManager->name,
                "manager_email"=>$findManager->email,
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
            "task_priority"=>"required",
            "task_id"=>"nullable"
           
        ]);
        

        if ($validator->fails()){
            $response=[
                "success" => false,
                "message"=> $validator->errors()
            ];
            return response()->json($response, 400);
        }

       /* $uniqueTaskName = Tasks::where("task_name",$validator->validated()['task_name'])->exists();

        if($uniqueTaskName){
            $fail=[
                "message"=> "Name is not unique"
            ];
            return response()->json($fail,500);*/

        if($validator->validated()['task_id'] != 0){
            $findTask = Tasks::where(["id"=>$validator->validated()['task_id'], "p_id"=>$validator->validated()['project_id']])->first();
            if($findTask != null){
                $update= $findTask->update([
                    "task_name"=>$validator->validated()['task_name'],
                    "deadline"=>$validator->validated()['deadline'],
                    "description"=>$validator->validated()['description'],
                    "p_id"=>$validator->validated()['project_id'],
                    "t_status"=>3,
                    "t_priority"=>$validator->validated()['task_priority'],

                ]);
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
                "priority_id"=>$findPriority->id,
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
            $findProjectname = Projects::where("id", $p->p_id)->first();

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
        $data = $request->json()->all();
        

        foreach($data as $d){
            AssignedTask::create([
                "task_id"=>$d['task_id'],
                "p_participant_id"=>$d['id']
            ]);
          
        }
 

        $success=[   
            "message"=>"Task attach was successfull!",
            "code"=>200,
            
        ];

        return response()->json($success, 200);
    }

    public function AttachMyself($project_id, $task_id){
        
        $user = JWTAuth::parseToken()->authenticate();
        
        
        $chekId = null;
        $check = ProjectParticipants::where(["p_id"=> $project_id,
                                            "user_id"=> $user->id,
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

    public function detachUser($projectId, $taskId, $userId){
        $participant = ProjectParticipants::where(["user_id"=>$userId,
                                                    "p_id"=> $projectId])->first();

        if($participant === null){
            $return=[
                "message" => "Datasbase error: User does not found."
            ];
            return response()->json($return);
        }else{
            $findAssignedTask = AssignedTask::where([
                "task_id"=>$taskId,
                "p_participant_id"=>$participant->id
            ])->first();

            if($findAssignedTask !== null){
                $findAssignedTask->delete();
                $success=[
                "message" => "User detach from this task"
                ];
                return response()->json($success,200);
            }else{
                $success=[
                "message" => "Datasbase error: Task or user does not found."
                ];
                return response()->json($success);
            }
        }

        

        
       
    }
    public function SendMessage($emitData, $projectId){
        $emit = json_decode($emitData);
       
        $message = $emit->message;
        $data = $emit->data;
        $participants = $emit->participants;

        if($participants != null && $message != null && $data != null){
            $user = JWTAuth::parseToken()->authenticate();

           
        
            /*foreach($participants as $p){
                $create = ChatMessages::create([
                    "p_id"=> $projectId,
                    "task_id"=>$data->id,
                    "sender_id"=>$user->id,
                    "receiver_id"=>$p->id,
                    "message"=>$message,

                ]);
            }*/
            $create = ChatMessages::create([
                "p_id"=> $projectId,
                "task_id"=>$data->id,
                "sender_id"=>$user->id,
                "message"=>$message,

            ]);
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
        }else{
            $fail=[
                "message" => "Undefined data sent."
            ];
            return response()->json($fail);
        }
       
    }
    public function getMessages($projectId,$taskId,$participants){

        if (empty($taskId)){
            $user = JWTAuth::parseToken()->authenticate();
            $filteredParticipants = json_decode($participants,true);
            if(empty($filteredParticipants)){
                throw new Exception('No participants in this project');
            }
            $filteredParticipants = array_filter($filteredParticipants, fn($participant) => $participant['id'] !== $user->id);
            
          
            $OtherUserMessages=[];
            foreach($filteredParticipants as $fp){
                $findOtherUserMessages= ChatMessages::where(["p_id" => $projectId,
                "task_id"=> null, "sender_id"=>$fp['id']])->orderBy('created_at', 'asc')->get();
                foreach($findOtherUserMessages as $foum){

                
                    //$findReceiver = User::where("id", $foum->receiver_id)->first();
                    $findSender = User::where("id", $foum->sender_id)->first();


                    $OtherUserMessages[]=[
                        
                        "sender_id"=>$findSender->id,
                        "sender_name"=>$findSender->name,
                        /*"receiver_id"=>$findReceiver->id,
                        "receiver_name"=>$findReceiver->name,
                        "receiver_email"=>$findReceiver->email,*/
                        "message"=>$foum->message,
                        "created_at"=>$foum->created_at
                    ];
                    
                }
            }

            
            $findOwnMessage = ChatMessages::where(["p_id" => $projectId,
            "task_id"=> null, "sender_id"=>$user->id])->orderBy('created_at', 'asc')->get();
                            
            $OwnMessage=[];
            
            foreach($findOwnMessage as $m){
                //$findReceiver = User::where("id", $m->receiver_id)->first();
                $findSender = User::where("id", $m->sender_id)->first();


                $OwnMessage[]=[
                  
                    "sender_id"=>$findSender->id,
                    "sender_name"=>$findSender->name,
                    /*"receiver_id"=>$findReceiver->id,
                    "receiver_name"=>$findReceiver->name,
                    "receiver_email"=>$findReceiver->email,*/
                    "message"=>$m->message,
                    "created_at"=>$m->created_at
                ];
                

            }
            $filterChatMessages=ChatMessages::where(["p_id" => $projectId,
            "task_id"=> null])->where("sender_id","!=",$user->id)->get();
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
            
            $allMessages=[];
            
            foreach($OwnMessage as $om){
               $allMessages[]=
               [
                "sender_id" => $om["sender_id"],
                "sender_name" => $om["sender_name"],
                /*"receiver_id" => $om["receiver_id"],
                "receiver_name" => $om["receiver_name"],
                "receiver_email" => $om["receiver_email"],*/
                "message" => $om["message"],
                "created_at"=>$om['created_at']
               ];
            }
            foreach ($OtherUserMessages as $otm) {
                $allMessages[] = [
                    "sender_id" => $otm["sender_id"],
                    "sender_name" => $otm["sender_name"],
                    /*"receiver_id" => $otm["receiver_id"],
                    "receiver_name" => $otm["receiver_name"],
                    "receiver_email" => $otm["receiver_email"],*/
                    "message" => $otm["message"],
                    "created_at" => $otm["created_at"]
                ];
            }
            
            usort($allMessages, function ($a, $b) {
                return strtotime($a['created_at']) - strtotime($b['created_at']);
            });
            $success=[
                "messageData"=> $allMessages,
                "currentUser_id"=> $user->id,
                "message"=>"Message Query was Successfull!"
            ];
            
            return response()->json($success, 200);
           
        }else{
            $user = JWTAuth::parseToken()->authenticate();
            $filteredParticipants = json_decode($participants,true);
            if(isEmpty($filteredParticipants)){
                throw new Exception('No participants in this task');
                /*return response()->json([
                    "message"=>"No participants in this task!"
                ],500);*/
            }            
            $filteredParticipants = array_filter($filteredParticipants, fn($participant) => $participant['id'] !== $user->id);
            
          
            $OtherUserMessages=[];
            foreach($filteredParticipants as $fp){
                $findOtherUserMessages= ChatMessages::where(["p_id" => $projectId,
                "task_id"=> $taskId, "sender_id"=>$fp['id']])->orderBy('created_at', 'asc')->get();
                foreach($findOtherUserMessages as $foum){

                
                    //$findReceiver = User::where("id", $foum->receiver_id)->first();
                    $findSender = User::where("id", $foum->sender_id)->first();


                    $OtherUserMessages[]=[
                        
                        "sender_id"=>$findSender->id,
                        "sender_name"=>$findSender->name,
                        /*"receiver_id"=>$findReceiver->id,
                        "receiver_name"=>$findReceiver->name,
                        "receiver_email"=>$findReceiver->email,*/
                        "message"=>$foum->message,
                        "created_at"=>$foum->created_at
                    ];
                   
                    
                }
            }
            
            $findOwnMessage = ChatMessages::where(["p_id" => $projectId,
            "task_id"=> $taskId, "sender_id"=>$user->id])->orderBy('created_at', 'asc')->get();
                            
            $OwnMessage=[];
            
            foreach($findOwnMessage as $m){
                //$findReceiver = User::where("id", $m->receiver_id)->first();
                $findSender = User::where("id", $m->sender_id)->first();


                $OwnMessage[]=[
                  
                    "sender_id"=>$findSender->id,
                    "sender_name"=>$findSender->name,
                    /*"receiver_id"=>$findReceiver->id,
                    "receiver_name"=>$findReceiver->name,
                    "receiver_email"=>$findReceiver->email,*/
                    "message"=>$m->message,
                    "created_at"=>$m->created_at
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
            $allMessages=[];
            
            foreach($OwnMessage as $om){
               $allMessages[]=
               [
                "sender_id" => $om["sender_id"],
                "sender_name" => $om["sender_name"],
                /*"receiver_id" => $om["receiver_id"],
                "receiver_name" => $om["receiver_name"],
                "receiver_email" => $om["receiver_email"],*/
                "message" => $om["message"],
                "created_at"=>$om['created_at']
               ];
            }
            foreach ($OtherUserMessages as $otm) {
                $allMessages[] = [
                    "sender_id" => $otm["sender_id"],
                    "sender_name" => $otm["sender_name"],
                    /*"receiver_id" => $otm["receiver_id"],
                    "receiver_name" => $otm["receiver_name"],
                    "receiver_email" => $otm["receiver_email"],*/
                    "message" => $otm["message"],
                    "created_at" => $otm["created_at"]
                ];
            }
            
            usort($allMessages, function ($a, $b) {
                return strtotime($a['created_at']) - strtotime($b['created_at']);
            });
            $success=[
                "messageData"=> $allMessages,
                "currentUser_id"=> $user->id,
                "message"=>"Message Query was Successfull!"
            ];
            
            return response()->json($success, 200);
        }

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
          
            $findChatMessageByProjectId = ChatMessages::where("p_id", $projects['p_id'])->where("task_id", null)->get();
            foreach($findChatMessageByProjectId as $findByProjectId){
                $existsInChatView = ChatView::where("chat_id", $findByProjectId['id'])->exists();
                if(!$existsInChatView){
                    $haveUnreadProjectMessages=true;
                    $Project[]=[
                        "UnreadProject_Project_id"=>$findByProjectId['p_id'],
                    ];
                }else{
                    $findOpenedMessage= ChatView::where("chat_id",$findByProjectId['id'])->get();
               
                    
                    foreach($findOpenedMessage as $opened){
                        if($opened['updated_at']<=$findByProjectId['created_at']){
                            $haveUnreadOpenedProjectMessages=true;
                            $Project[]=[
                                "UnreadOpenedProject_Chat_id"=>$opened['chat_id'],
                            ];
                        }
                    }
                }
                
                
            }
            
            $assignedTasks = AssignedTask::where("p_participant_id",$projects['id'] )->get();
            

            foreach($assignedTasks as $findByTaskId){
                
                $findChatMessageByTaskId=ChatMessages::where("p_id", $projects['p_id'])->where("task_id", $findByTaskId['task_id'])->get();
                foreach($findChatMessageByTaskId as $ChatMessage){
                    $existsInChatView = ChatView::where("chat_id", $ChatMessage['id'])->exists();
                    if(!$existsInChatView){
                        $haveUnreadTaskMessages=true;
                        $Task[]=[
                            "UnreadTaskMessages_Chat_id"=>$ChatMessage['id']
                        ];
                    }else{
                        $findOpenedMessage= ChatView::where("chat_id",$ChatMessage['id'])->get();

                        $enterTheHook=true;
                        foreach($findOpenedMessage as $opened){
                            if($findByTaskId['created_at']>$opened['updated_at']){
                                $haveUnreadOpenedTaskMessages=true;
                                $Task[]=[
                                    "UnreadOpenedTask_Chat_id"=>$opened["chat_id"]
                                ];
                            }
                        }
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


        /*$filterUserChatMessages= ChatMessages::where([
            "receiver_id"=>$user->id,
            
        ])->where("sender_id", '!=', $user->id)->get();
        
        $messagesArray=[];
        $count=0;
        $mergeData=[];
        foreach($filterUserChatMessages as $messages){
           $filterChatViewing = ChatView::where("chat_id", $messages->id)->where("updated_at",">",$messages["created_at"])->get();
           if($filterChatViewing){
                $findNeverOpendChat = ChatView::where("chat_id",'!=', $messages["id"])->get();

                foreach($filterChatViewing as $view){
                    foreach($findNeverOpendChat as $neveropend){
                        $mergeData[]=[
                            "chatview"=>$view,
                            "widthout"=>$neveropend,
                        ];
                        $count++;
                    }
                    
                }
                
            }
           

        }
        return response()->json([
                    "data"=> $mergeData,
                    "count"=>$count
                ]);
        /*if($filterChatViewing->isEmpty()){
        
            foreach($filterUserChatMessages as $messages){
                $count++;
                $messagesArray[]=[
                    "message"=>$messages
                ];
            }
            
            
            
            
            return response()->json([
                "unreadmessages" => $count,
                "messages"=>$messagesArray,
                "filterChatViewing"=>$mergeData
            ],200);
    
        
            
        }else if(!($filterChatViewing->isEmpty())){
            foreach($filterChatViewing as $view){
                if($view['updated_at']>=$messages['created_at']){
                    $count++;
                }
            }
            foreach($filterUserChatMessages as $messages){
                $findNeverOpendChat = ChatView::where("chat_id",'!=', $messages["id"])->get();
  
            }
            if(!$findNeverOpendChat->isEmpty()){
            
                $count += $findNeverOpendChat->count();
    
                return response()->json([
                    "unreadmessages" => $count,
                    
                ],200);
            }
            
        }
           
        
            
            
        

        return response()->json([
            "unreadmessages" => $count,
            "messages"=>$messagesArray,
        ],200);*/
            

        

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
            }else{
                $success[]=[
                    "message"=> "You have no access permission!"
                ];
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
        }else if($haveAdminRole == true){
            
            $haveAdminRole = true;
            $success[]=[
                "admin"=>$AdminButtons,
            ];
            
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
    





    

        
    
}
