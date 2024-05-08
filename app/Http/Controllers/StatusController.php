<?php

namespace App\Http\Controllers;

use App\Models\Projects;
use App\Models\ProjectsStatus;
use App\Models\TaskPriorities;
use App\Models\Tasks;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StatusController extends Controller
{
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
            return response()->json($getProjectsStatus,200);
        }else{
            $getTasksStatus = TaskStatus::all();
            $success=[
                "message"=>"That's it!",
                "status"=>$getTasksStatus
            ];
            return response()->json($getTasksStatus,200);
        }
    }


    public function setStatus(Request $request){
        return DB::transaction(function() use(&$request){
            $ProjectId=$request->input('projectId');
            $TaskId=$request->input('taskId');
            $StatusId=$request->input('StatusId');
            $PriorityId=$request->input('priorityId');

            $data = [
                'ProjectId' => $ProjectId,
                'TaskId' =>  $TaskId,
                'StatusId'=>$StatusId,
                'PriorityId'=>$PriorityId,
            ];

            $rules = [
                'ProjectId' => 'required',
                'TaskId' => 'nullable',
                'StatusId' => 'nullable',
                'PriorityId'=>'nullable',
            ];
            $validator = Validator::make($data, $rules);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
            if($StatusId == 'undefined'){
                throw new \Exception("Status set is reqired!");
            }
            if($StatusId === null && $TaskId!==null && $PriorityId===null){
                throw new \Exception('Operation canceled');
            }
            $success=[];

            if($TaskId == null){
                $findProject= Projects::where("id", $ProjectId)->first();
                if(!empty($findProject)){
                    $findProject->touch();
                    $findProject->update([
                        "p_status"=>$StatusId
                    ]);
                    $success[]=[
                        "message"=>"Update Successful!",
                    ];
                    return response()->json($success,200);
                }
            }else{
                $findProjectTasks = Tasks::where(["p_id"=>$ProjectId, "id"=>$TaskId])->first();
                $findProjectTasks->touch();
                if($StatusId !== null){
                    $findProjectTasks->update([
                        "t_status"=>$StatusId,
                    ]);
                }
                if($PriorityId !== null){
                    $findProjectTasks->update([
                        "t_priority"=>$PriorityId,
                    ]);
                }
                $success[]=[
                    "message"=>"Update Successful!",
                ];
                $success[]=[
                    "message"=>"Update Successful!",
                ];
                return response()->json($success,200);
            }
        });
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

                throw new \Exception("Project does not exists!");
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
                throw new \Exception("Task does not exists!");
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
}
