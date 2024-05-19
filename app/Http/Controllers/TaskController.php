<?php

namespace App\Http\Controllers;

use App\Http\Controllers\TaskController\Exception;
use App\Models\AssignedTask;
use App\Models\ProjectParticipants;
use App\Models\Projects;
use App\Models\ProjectsStatus;
use App\Models\TaskPriorities;
use App\Models\Tasks;
use App\Models\TaskStatus;
use App\Models\User;
use App\Traits\PermissionTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class TaskController extends Controller
{
    use PermissionTrait;
    public function getPriorities(){
        $priorities = TaskPriorities::all();
        $success=[];
        if(!empty($priorities)) {
            foreach ($priorities as $p) {
                $success[] = [
                    "id" => $p->id,
                    "name" => $p->task_priority
                ];
            }
            return response()->json($success,200);
        }else{
            throw new \Exception('Database error occurred.');
        }
    }
    public function createTask(Request $request){
        return DB::transaction(function() use(&$request){
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
        });
    }

    public function getTasks(Request $request){
        $user=JWTAuth::parseToken()->authenticate();

        $id = $request->input('projectId');
        $sortData = $request->input('sortData');
        $filterData = $request->input('filterData');

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
        $findUserasParticipant = $this->findUserAsParticipant($id, $user->id);
        $success=[];

        foreach($tasks as $task){
            $findPriority = TaskPriorities::where("id", $task->t_priority)->first();
            $findStatus = TaskStatus::where("id",$task->t_status)->first();
            $findTaskParticiantsCount = AssignedTask::where("task_id", $task->id)->count();
            $findMyTask = $findUserasParticipant ? AssignedTask::where(["task_id"=>$task->id,"p_participant_id"=>$findUserasParticipant->id])->exists() : false;
            $success[]=[
                "taskData"=>
                    [
                        "task_id"=>$task->id,
                        "task_name"=>$task->task_name,
                        "deadline"=>$task->deadline,
                        "description"=>$task->description,
                        "status"=>$findStatus->task_status,
                        "priority_id"=>$findPriority->id,
                        "priority"=>$findPriority->task_priority,
                        "employees"=>$findTaskParticiantsCount,
                        "mytask"=>$findMyTask,
                    ]
            ];
        }

        if(!empty($success)){
            $success[]=[
                "roles"=>[
                    "haveManagerRole"=>$request->isProjectManager,
                    "haveAdminRole"=>$request->haveAdminRole,
                    "haveParticipantRole"=>$request->isProjectParticipant,
                ]
            ];
            return response()->json($success,200);
        }else{
            $success=[
                "message"=>"You have no tasks in this project!",
                "haveManagerRole"=>$request->isProjectManager,
                "haveAdminRole"=>$request->haveAdminRole ?? false,
                "haveParticipantRole"=>$request->isProjectParticipant,
                "code"=>404,
            ];
            return response()->json($success,404);
        }
    }
    public function AssignEmpoyleeToTask(Request $request){
        return DB::transaction(function() use(&$request){
            if($request->haveManagerRole || $request->haveAdminRole){
                $data=$request->input('requestData');
                $remove = $request->input('removeData');
                $task_id = $request->input('task_id');
                $project_id = $request->input('project_id');

                if(!empty($data)){
                    foreach($data as $d){
                        $findAssignedUser = $this->checkTaskAssigned($d['id'],$d['task_id']);
                        if($findAssignedUser==true){
                            throw new \Exception("User already assigned to this task");
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
                            throw new \Exception("Datasbase error: User does not found!");

                        }else{
                            $findAssignedTask = AssignedTask::where([
                                "task_id"=>$task_id,
                                "p_participant_id"=>$findParticipantId['id']
                            ])->first();

                            if(!empty($findAssignedTask)){
                                $findAssignedTask->delete();
                            }else{
                                throw new \Exception( "Datasbase error occured!");
                            }
                        }
                    }
                }
                $success = ["message"=>"Success!"];
                return response()->json($success,200);
            }else{
                throw new \Exception("Denied!");
            }
        });
    }

    public function AttachMyself(Request $request){
        return DB::transaction(function() use($request){
            $user = JWTAuth::parseToken()->authenticate();

            $project_id=$request->projectId;
            $task_id=$request->taskId;

            $checkUserInProject = $this->findUserAsParticipant($project_id, $user->id);
            if(!empty($checkUserInProject)){
                $alreadyAssigned= $this->checkTaskAssigned($checkUserInProject->id,$task_id);
                if($alreadyAssigned===true){
                    throw new \Exception("Already attached yourself!");
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
        });
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
    public function Completed(Request $request){
        return DB::transaction(function() use(&$request){
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

            $findUserasParticipant = $this->findUserAsParticipant($ProjectId, $user->id);
            if(empty($findUserasParticipant)){
                throw new \Exception("Denied!");
            }else{
                $findAssignedTask=AssignedTask::where(["p_participant_id" => $findUserasParticipant->id, "task_id"=>$TaskData['task_id']?? $TaskData['id']])->first();
                if(empty($findAssignedTask)){
                    throw new \Exception("Denied!");

                }else{
                    $findTask=Tasks::where("id", $TaskData['task_id']?? $TaskData['id'])->first();
                    $findStatus = TaskStatus::where("task_status", "Completed")->first();
                    if(!empty($findTask)){
                        $findTask->update([
                            "t_status"=>$findStatus->id
                        ]);
                        $findTask->save();

                    }else{
                        throw new \Exception("Task does not exist!");
                    }
                }
            }
            $success[]=[
                "message"=>"Nice job!"
            ];
            return response()->json($success,200);
        });
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
                "taskData"=>[
                    "id" => $task->id,
                    "task_name" => $task->task_name,
                    "deadline" => $task->deadline,
                    "description" => $task->description,
                    "status" => $status['task_status'],
                    "priority" => $findPriority->task_priority,
                    "priorityId" => $findPriority->id
                ],
                "projectData"=>[
                    "name" => $findProjectname['p_name'],
                    "project_id" => $findProjectname['id'],
                ]
            ];
        }
        if (empty($success)) {
            throw new \Exception("You have no tasks!");
        }

        return response()->json($success, 200);
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

        if($request->haveManagerRole){
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
                $findUserasParticipant = $this->findUserAsParticipant($task->p_id,$user->id);
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
                    "haveManagerRole"=>$request->haveManagerRole,
                    "p_id"=>$task->p_id,
                    "myTask"=>$findMyTask
                ];
            }

            if(!empty($success)){
                $ids=[];
                return response()->json($success,200);
            }else{
                throw new \Exception("You have no managed tasks!");

            }
        }else{
            throw new \Exception("Access Denied");
        }
    }
    public function managedCompletedTasks(Request $request){
        $user=JWTAuth::parseToken()->authenticate();

        if($request->haveManagerRole){
            $findCompleted = TaskStatus::where("task_status", "Completed")->pluck('id');
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
                    "haveManagerRole"=>$request->haveManagerRole,
                ];
            }

            if(!empty($success)){

                return response()->json($success,200);
            }else{

                $success=[
                    "message"=>"You have no tasks to be approved",
                    "haveManagerRole"=>$request->haveManagerRole,
                    "code"=>404,
                ];
                return response()->json($success,404);
            }
        }else{
            throw new Exception("Access Denied");
        }
    }
    public function acceptAllTask(Request $request){
        return DB::transaction(function() use(&$request){
            $user=JWTAuth::parseToken()->authenticate();

            if($request->haveManagerRole){
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
                    "haveManagerRole"=>$request->haveManagerRole,
                    "code"=>200,
                ];
                return response()->json($success,200);
            }else{
                throw new \Exception("Access Denied");
            }
        });
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
}
