<?php

namespace App\Http\Controllers;

use App\Models\AssignedTask;
use App\Models\ChatMessages;
use App\Models\ChatView;
use App\Models\ProjectParticipants;
use App\Models\Projects;
use App\Models\Tasks;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class MessageController extends Controller
{
    public function SendMessage(Request $request){
        return DB::transaction(function() use($request){
            $validator = Validator::make($request->all(),[
                "message"=>"required",
                "projectId"=>"required",
                "taskId"=>"nullable"
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $message= $request->input('message');
            $taskId = $request->input('taskId');
            $projectId =$request->input('projectId');

            $user = JWTAuth::parseToken()->authenticate();

            try{
                ChatMessages::create([
                    "p_id"=> $projectId,
                    "task_id"=>$taskId?:null,
                    "sender_id"=>$user->id,
                    "message"=>$message,
                ]);
            }catch (\Exception $e){
                throw new $e('Fail under sending message!');
            }

            $success=[
                "message"=>"That's it!",
                "code"=>200,
            ];

            return response()->json($success);
        });
    }
    public function getMessages(Request $request){

        $user = JWTAuth::parseToken()->authenticate();

        $projectId = $request->input('projectId');
        $taskId = $request->input('taskId');

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

        foreach($findProjects as $projects){

            $findChatMessageByProjectId = ChatMessages::where("p_id", $projects['p_id'])->where("task_id", null)->where("sender_id","!=", $user->id)->get();

            foreach($findChatMessageByProjectId as $findByProjectId){
                $existsInChatView = ChatView::where("chat_id", $findByProjectId['id'])->exists();
                if($existsInChatView===false){
                    $haveUnreadProjectMessages=true;
                    $Project[]=[
                        "UnreadProject_Project_id"=>$findByProjectId['p_id'],
                    ];
                }
            }

            $assignedTasks = AssignedTask::where("p_participant_id",$projects['id'] )->get();

            foreach($assignedTasks as $findByTaskId){

                $findChatMessageByTaskId=ChatMessages::where("p_id", $projects['p_id'])->where("task_id", $findByTaskId['task_id'])->where("sender_id","!=", $user->id)->get();
                foreach($findChatMessageByTaskId as $ChatMessage){
                    $existsInChatView = ChatView::where("chat_id", $ChatMessage['id'])->exists();
                    if($existsInChatView===false){
                        $haveUnreadTaskMessages=true;
                        $Task[]=[
                            "UnreadTaskMessages_Task_id"=>$ChatMessage['task_id'],
                            "UnreadTask_Project_id"=>$ChatMessage['p_id']
                        ];
                    }
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
                    if($existsInChatView===false){
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
        ];
        return response()->json($success,200);

    }
}
