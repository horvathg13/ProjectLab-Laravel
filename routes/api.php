<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post("register",[App\Http\Controllers\RegisterControllers\RegisterController::class, "register"]);
Route::post("login",[App\Http\Controllers\AuthControllers\Auth_Controller::class, "login"]);
Route::post("logout",[App\Http\Controllers\AuthControllers\Auth_Controller::class, "logout"]);
Route::post("getUserData",[App\Http\Controllers\API\Api_Controller::class, "getUserData"]);
Route::post("createuser",[App\Http\Controllers\RegisterControllers\RegisterController::class, "createUser"]);

Route::get("findUser/{token} ",[App\Http\Controllers\RegisterControllers\RegisterController::class, "findUser"]);
Route::post("resetpassword",[App\Http\Controllers\RegisterControllers\RegisterController::class, "resetPassword"]);
Route::post("createrole",[App\Http\Controllers\API\Api_Controller::class, "createRole"]);
Route::post("getroles",[App\Http\Controllers\API\Api_Controller::class, "getRoles"]);
Route::post("getusers",[App\Http\Controllers\API\Api_Controller::class, "getUsers"]);
Route::post("user-to-role/{id}/{role}",[App\Http\Controllers\API\Api_Controller::class, "userToRole"]);
Route::post("password-reset-manual/{id}",[App\Http\Controllers\RegisterControllers\RegisterController::class, "passwordResetManual"]);
Route::post("createproject",[App\Http\Controllers\API\Api_Controller::class, "createProject"]);
Route::post("getprojects",[App\Http\Controllers\API\Api_Controller::class, "getProjects"]);
Route::post("bann-user/{id}",[App\Http\Controllers\RegisterControllers\RegisterController::class, "bannTheUser"]);
Route::post("getpriorities",[App\Http\Controllers\API\Api_Controller::class, "getPriorities"]);
Route::post("createtask",[App\Http\Controllers\API\Api_Controller::class, "createtask"]);
Route::post("getprojectparticipants/{id}",[App\Http\Controllers\API\Api_Controller::class, "getProjectParticipants"]);
Route::post("createparticipants",[App\Http\Controllers\API\Api_Controller::class, "createParticipants"]);
Route::post("projects/{id}/tasks",[App\Http\Controllers\API\Api_Controller::class, "getTasks"]);
Route::post("projects/{id}",[App\Http\Controllers\API\Api_Controller::class, "getProjectById"]);
Route::post("assign-employee-to-task",[App\Http\Controllers\API\Api_Controller::class, "AssignEmpoyleeToTask"]);
Route::post("projects/{project_id}/tasks/{task_id}",[App\Http\Controllers\API\Api_Controller::class, "AttachMyself"]);
Route::post("getActiveEmployees/{task_id}",[App\Http\Controllers\API\Api_Controller::class, "getActiveEmployees"]);
Route::post("detach/{projectId}/{taskId}/{userId}",[App\Http\Controllers\API\Api_Controller::class, "detachUser"]);
Route::post("send-message/{emitData}/{projectId}",[App\Http\Controllers\API\Api_Controller::class, "sendMessage"]);
Route::post("get-messages/project-id/{projectId}/task-id/{taskId}/participants/{participants}",[App\Http\Controllers\API\Api_Controller::class, "getMessages"]);