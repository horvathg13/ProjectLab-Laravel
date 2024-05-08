<?php

namespace App\Http\Controllers;

use App\Http\Controllers\PermissionController as Permission;
use App\Models\AssignedTask;
use App\Models\ChatMessages;
use App\Models\ChatView;
use App\Models\ProjectParticipants;
use App\Models\Projects;
use App\Models\ProjectsStatus;
use App\Models\Tasks;
use App\Models\TaskStatus;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationController extends Controller
{
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
    public function getManagerNotification(){
        $user=JWTAuth::parseToken()->authenticate();
        $haveManagerRole = Permission::checkManager($user->id);

        if($haveManagerRole===true){
            $findActiveProjects=ProjectsStatus::where("p_status","Active")->first();
            $findTasksStatus = TaskStatus::where("task_status", "Completed")->first();
            $projectsQuery= Projects::where(["p_manager_id"=> $user->id, "p_status"=>$findActiveProjects->id])->pluck('id');
            $taskQuery = Tasks::whereIn("p_id",$projectsQuery)->where("t_status",$findTasksStatus->id);

            $tasks = $taskQuery->count();


            return response()->json($tasks,200);

        }

    }
}
