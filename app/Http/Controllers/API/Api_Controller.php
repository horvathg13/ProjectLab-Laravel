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
                $findManagerRoleId = Roles::where("role_name", "Manager")->first();
                $findManagerGlobalRole = RoleToUser::where(["role_id"=>$findManagerRoleId->id, "user_id"=>$validator->validated()['p_manager_id']])->exists();
                if($findManagerGlobalRole==true){
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
                }else{
                    throw new Exception("User has no manager role in the system!");
                }
               
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
        /*$today=now();
        $projects = Projects::where("deadline", ">=", $today)->get();*/
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
            "deadline"=> "required|date_format:Y-m-d|after_or_equal:today",
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
                    "t_status"=>$findTaskStatus['id'],
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
       

    

    public function getTasks($id){
        $user=JWTAuth::parseToken()->authenticate();
        $haveManagerRole=Projects::where(["p_manager_id"=>$user->id, "id"=>$id])->exists();
        
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
                "haveManagerRole"=>$haveManagerRole,
            ];
        }

        if(!empty($success)){
            return response()->json($success,200);
        }else{
            $success=[   
                "message"=>"You have no tasks in this project!",
                "haveManagerRole"=>$haveManagerRole,
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
            "participants" => "required",
            "project" => "required"
        ]);

        if ($validator->fails()){
            $response=[
                "success" => false,
                "message"=> $validator->errors()
            ];
            return response()->json($response, 400);
        }
        $participants = $validator->validated()['participants'];
        $project = $validator->validated()['project'];
        foreach($participants as $parti){
            $findParticipants= ProjectParticipants::where(["user_id"=>$parti['id'], "p_id"=>$project['project_id']])->exists();
            if($findParticipants == true){
                throw new Exception("Participants already attached!");
            }else{
                $find_status_id = ProjectsStatus::where("p_status", $project["status"])->first();
                ProjectParticipants::create([
                    "user_id"=>$parti['id'],
                    "p_id"=>$project['project_id'],
                    'p_status'=>$find_status_id->id

                ]);
            }
            
        };
        $success=[
            "message"=>"Thats it! Participants created Successfull",
            "code"=>200,
            
        ];

        return response()->json($success,200);
       
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
        
        

        if($participants != null && $message != null && $data != null){
            $user = JWTAuth::parseToken()->authenticate();

            $create = ChatMessages::create([
                "p_id"=> $projectId,
                "task_id"=>$data['id'],
                "sender_id"=>$user->id,
                "message"=>$message,

            ]);

            $create= true;
            if(!$create){
                $success=[   
                    "message"=>"Fail under sending message!",
                    "code"=>500,
                ];
                return response()->json($success);
            }else{
                $success=[   
                    "message"=>"That's it!",
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

       
           
        
        $user = JWTAuth::parseToken()->authenticate();
       
        $allMessages=[];
        $findMessages= ChatMessages::where(["p_id" => $projectId,
        "task_id"=> $taskId])->orderBy('created_at', 'asc')->get();
        foreach($findMessages as $foum){

        
            $findSender = User::where("id", $foum->sender_id)->first();


            $allMessages[]=[
                
                "sender_id"=>$findSender->id,
                "sender_name"=>$findSender->name,
                
                "message"=>$foum->message,
                "created_at"=>$foum->created_at
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
                if(!$existsInChatView){
                    $haveUnreadProjectMessages=true;
                    $Project[]=[
                        "UnreadProject_Project_id"=>$findByProjectId['p_id'],
                    ];
                }else{
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
                }
                
                
            }
            
            $assignedTasks = AssignedTask::where("p_participant_id",$projects['id'] )->get();
            

            foreach($assignedTasks as $findByTaskId){
                
                $findChatMessageByTaskId=ChatMessages::where("p_id", $projects['p_id'])->where("task_id", $findByTaskId['task_id'])->where("sender_id","!=", $user->id)->get();
                foreach($findChatMessageByTaskId as $ChatMessage){
                    $existsInChatView = ChatView::where("chat_id", $ChatMessage['id'])->exists();
                    if(!$existsInChatView){
                        $haveUnreadTaskMessages=true;
                        $Task[]=[
                            "UnreadTaskMessages_Task_id"=>$ChatMessage['task_id'],
                            "UnreadTask_Project_id"=>$ChatMessage['p_id']
                        ];
                    }else{
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
            'StatusId' => 'required',
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
            if($SetAllTaskBool===false){
                
                $findProjectTasks->touch();
                $findProjectTasks->update([
                    "t_status"=>$StatusId,
                    
                ]);
                $success[]=[
                    "message"=>"Update Successfull!",
                ];
            }else{
                
                $findProjectTasks = Tasks::where("p_id", $ProjectId)->get();
                if(empty($findProjectTasks)){
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
                        "dedadline"=>$task->deadline,
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
        $findUrgent=ProjectsStatus::where("p_status","Active")->first();
        $findUserasProjectManager= Projects::where(["p_manager_id" =>$user->id, "p_status"=>$findUrgent['id'] ])->where("deadline", "<=", $urgentDay)->get();
        
        if($findUserasProjectManager->isNotEmpty()){
            foreach($findUserasProjectManager as $manager){
                //$computedDays = now()->diffInDays($manager->deadline);
                //if($computedDays <= 5){
                    $findUrgent=ProjectsStatus::where("id",$manager->p_status)->first();
                    /*$manager->update([
                      "p_status"=>$findUrgent['id'],
                    ]);
                    $manager->save();*/
                   
                    $success[]=[
                        "id"=>$manager->id,
                        "type"=>"Project",
                        "title"=>$manager->p_name,
                        "status"=>$findUrgent['p_status'],
                        "deadline"=>$manager->deadline,
                        //"days"=>$computedDays,
                        
                    ];
                //}
              
                
                
                /*$findCompleted=ProjectsStatus::where(["p_status","Completed"])->first();
                if($computed->days <= 0 && $manager->p_status !== $findCompleted['id']){
                    $success[]=[
                        "id"=>$manager->id,
                        "type"=>"Project",
                        "title"=>$manager->p_name,
                        "status"=>$findCompleted['p_status'],
                        "deadline"=>$manager->deadline,
                        "days"=>$computed->days,
                        
                    ];
                }*/
            }
            
           
        }
        
        $findUserasParticipant=ProjectParticipants::where("user_id", $user->id)->get();
        foreach($findUserasParticipant as $parti){
            $findAssignedTask=AssignedTask::where("p_participant_id", $parti['id'])->get();
            foreach($findAssignedTask as $task){
                $findTask=Tasks::where("id",$task['task_id'])->where("deadline", "<=", $urgentDay)->get();
                if( $findTask->isNotEmpty()){
                    foreach($findTask as $t){
                       // $computedDays = now()->diffInDays($t['deadline']);
                        $findTaskStatus=TaskStatus::where("id", $t->t_status)->first();

                        //if($computedDays <= 5){
                            $success[]=[
                                "id"=>$t->id,
                                "type"=>"Task",
                                "title"=>$t->task_name,
                                "status"=>$findTaskStatus['task_status'],
                                "deadline"=>$t->deadline,
                                //"days"=>$computedDays
                            ];
                        //}
                    }
                    
                }

            }
            
        }
            
        

        return response()->json($success, 200);
        
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

    public function MyTasks(){
        $jwt = JWTAuth::parseToken();
        $user= $jwt->authenticate();
        $success=[];
        $findProjectStatus = ProjectsStatus::where("p_status", "Active")->first();
        $findUserasParticipant=ProjectParticipants::where(["user_id"=>$user->id, "p_status"=>$findProjectStatus['id']])->get();

        foreach($findUserasParticipant as $parti){
            $findAssignedTask=AssignedTask::where("p_participant_id", $parti->id)->get();
            foreach($findAssignedTask as $assigned){
                $findStatus = TaskStatus::where('task_status', 'Active')->orWhere('task_status', 'Completed')->get();
                foreach($findStatus as $status){
                    $findTask = Tasks::where(["id"=>$assigned->task_id, "t_status"=>$status->id])->get();

                    foreach($findTask as $task){
                        //$findStatus = TaskStatus::where("id", $task->t_status)->first();
                        $findPriority = TaskPriorities::where("id", $task->t_priority)->first();
                        $findProjectname = Projects::where("id", $task->p_id)->first();

                        $success[]=[
                            "id"=>$task->id,
                            "name"=>$task->task_name,
                            "deadline"=>$task->deadline,
                            "description"=>$task->description,
                            "projectName"=>$findProjectname['p_name'],
                            "projectId"=>$findProjectname['id'],
                            "status"=>$status->task_status,
                            "priority"=>$findPriority->task_priority,
                            "priorityId"=>$findPriority->id
                            
                        ];
                    }
                }
                
                
            }
           
        }
        if(empty($success)){
            throw new Exception("You have no tasks!");
        }
        return response()->json($success,200);
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
    
        
    
}
