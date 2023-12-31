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
Route::post("user-to-role",[App\Http\Controllers\API\Api_Controller::class, "userToRole"]);
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
Route::post("send-message",[App\Http\Controllers\API\Api_Controller::class, "sendMessage"]);
Route::post("get-messages",[App\Http\Controllers\API\Api_Controller::class, "getMessages"]);
Route::post("get-unread-messages",[App\Http\Controllers\API\Api_Controller::class, "getUnreadMessages"]);
Route::post("get-buttons/{projecId}",[App\Http\Controllers\API\Api_Controller::class, "getProjectandTaskButtons"]);
Route::post("get-users-buttons",[App\Http\Controllers\API\Api_Controller::class, "getUsersButton"]);
Route::post("get-status/{ProjectId}/{TaskId}",[App\Http\Controllers\API\Api_Controller::class, "getStatus"]);
Route::post("set-status",[App\Http\Controllers\API\Api_Controller::class, "setStatus"]);
Route::post("filter-status/{ProjectId}/{Task}/{StatusId}",[App\Http\Controllers\API\Api_Controller::class, "statusFilterProjectOrTask"]);
Route::post("notifications",[App\Http\Controllers\API\Api_Controller::class, "Notifications"]);
Route::post("completed",[App\Http\Controllers\API\Api_Controller::class, "Completed"]);
Route::post("get-my-tasks",[App\Http\Controllers\API\Api_Controller::class, "MyTasks"]);
Route::post("getUserRole",[App\Http\Controllers\API\Api_Controller::class, "getUserRole"]);
Route::post("sort",[App\Http\Controllers\API\Api_Controller::class, "Sort"]);
Route::post("add-favorite-project",[App\Http\Controllers\API\Api_Controller::class, "addFavoriteProject"]);
Route::post("remove-favorite-project",[App\Http\Controllers\API\Api_Controller::class, "removeFromFavorite"]);
Route::post("get-favorite-projects",[App\Http\Controllers\API\Api_Controller::class, "getFavoriteProjects"]);
Route::post("get-manager-projects",[App\Http\Controllers\API\Api_Controller::class, "getManagerProjects"]);
Route::post("get-manager-tasks",[App\Http\Controllers\API\Api_Controller::class, "getManagerTasks"]);
Route::post("get-manager-notification",[App\Http\Controllers\API\Api_Controller::class, "getManagerNotification"]);
Route::post("managed-completed-tasks",[App\Http\Controllers\API\Api_Controller::class, "managedCompletedTasks"]);
Route::post("accept-all-task",[App\Http\Controllers\API\Api_Controller::class, "acceptAllTask"]);
Route::post("leave-project",[App\Http\Controllers\API\Api_Controller::class, "leaveProject"]);
Route::post("count-of-my-active-tasks",[App\Http\Controllers\API\Api_Controller::class, "countOfMyTasks"]);
Route::post("save-profile-data",[App\Http\Controllers\API\Api_Controller::class, "saveProfileData"]);
Route::post("getManagers",[App\Http\Controllers\API\Api_Controller::class, "getManagers"]);
Route::post("acessControllForTasks",[App\Http\Controllers\API\Api_Controller::class, "AccessControllForTasks"]);
Route::post("getEmployees",[App\Http\Controllers\API\Api_Controller::class, "getEmployees"]);
