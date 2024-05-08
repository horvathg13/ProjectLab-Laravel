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

/*RegisterController*/
Route::post("register",[\App\Http\Controllers\RegisterController::class, "register"]);
Route::post("createuser",[\App\Http\Controllers\RegisterController::class, "createUser"]);
Route::get("findUser/{token} ",[\App\Http\Controllers\RegisterController::class, "findUser"]);
Route::post("resetpassword",[\App\Http\Controllers\RegisterController::class, "resetPassword"]);
Route::post("password-reset-manual/{id}",[\App\Http\Controllers\RegisterController::class, "passwordResetManual"]);
Route::post("ban-user/{id}",[\App\Http\Controllers\RegisterController::class, "banTheUser"]);

/*AuthController*/
Route::post("login",[\App\Http\Controllers\AuthController::class, "login"]);
Route::post("logout",[\App\Http\Controllers\AuthController::class, "logout"]);

/*PermissionController*/
Route::post("getUserRole",[\App\Http\Controllers\PermissionController::class, "getUserRole"]);
Route::post("getroles",[\App\Http\Controllers\PermissionController::class, "getRoles"]);

/*UserController*/
Route::post("getusers",[\App\Http\Controllers\UserController::class, "getUsers"]);
Route::post("getUserData",[\App\Http\Controllers\UserController::class, "getUserData"]);
Route::post("getEmployees",[\App\Http\Controllers\UserController::class, "getEmployees"]);
Route::post("getManagers",[\App\Http\Controllers\UserController::class, "getManagers"]);
Route::post("user-to-role",[\App\Http\Controllers\UserController::class, "userToRole"]);
Route::post("save-profile-data",[\App\Http\Controllers\UserController::class, "saveProfileData"]);

/*ProjectController*/
Route::post("createproject",[\App\Http\Controllers\ProjectController::class, "createProject"]);
Route::post("getprojects",[\App\Http\Controllers\ProjectController::class, "getProjects"]);
Route::post("projects/{id}",[\App\Http\Controllers\ProjectController::class, "getProjectById"]);
Route::post("getprojectparticipants/{id}",[\App\Http\Controllers\ProjectController::class, "getProjectParticipants"]);
Route::post("createparticipants",[\App\Http\Controllers\ProjectController::class, "createParticipants"]);
Route::post("add-favorite-project",[\App\Http\Controllers\ProjectController::class, "addFavoriteProject"]);
Route::post("remove-favorite-project",[\App\Http\Controllers\ProjectController::class, "removeFromFavorite"]);
Route::post("get-favorite-projects",[\App\Http\Controllers\ProjectController::class, "getFavoriteProjects"]);
Route::post("get-manager-projects",[\App\Http\Controllers\ProjectController::class, "getManagerProjects"]);
Route::post("leave-project",[\App\Http\Controllers\ProjectController::class, "leaveProject"]);

/*TaskController*/
Route::post("createtask",[\App\Http\Controllers\TaskController::class, "createtask"]);
Route::post("getpriorities",[\App\Http\Controllers\TaskController::class, "getPriorities"]);
Route::post("projects/{id}/tasks",[\App\Http\Controllers\TaskController::class, "getTasks"]);
Route::post("assign-employee-to-task",[\App\Http\Controllers\TaskController::class, "AssignEmpoyleeToTask"]);
Route::post("task-attach-to-myself",[\App\Http\Controllers\TaskController::class, "AttachMyself"]);
Route::post("getActiveEmployees/{task_id}",[\App\Http\Controllers\TaskController::class, "getActiveEmployees"]);
Route::post("completed",[\App\Http\Controllers\TaskController::class, "Completed"]);
Route::post("get-my-tasks",[\App\Http\Controllers\TaskController::class, "MyTasks"]);
Route::post("get-manager-tasks",[\App\Http\Controllers\TaskController::class, "getManagerTasks"]);
Route::post("managed-completed-tasks",[\App\Http\Controllers\TaskController::class, "managedCompletedTasks"]);
Route::post("accept-all-task",[\App\Http\Controllers\TaskController::class, "acceptAllTask"]);
Route::post("count-of-my-active-tasks",[\App\Http\Controllers\TaskController::class, "countOfMyTasks"]);
Route::post("accessControlForTasks",[\App\Http\Controllers\TaskController::class, "AccessControlForTasks"]);

/*MessageController*/
Route::post("send-message",[\App\Http\Controllers\MessageController::class, "sendMessage"]);
Route::post("get-messages",[\App\Http\Controllers\MessageController::class, "getMessages"]);
Route::post("get-unread-messages",[\App\Http\Controllers\MessageController::class, "getUnreadMessages"]);

/*ButtonController*/
Route::post("get-buttons/{projecId}",[\App\Http\Controllers\ButtonController::class, "getProjectandTaskButtons"]);
Route::post("get-users-buttons",[\App\Http\Controllers\ButtonController::class, "getUsersButton"]);

/*StatusController*/
Route::post("get-status/{ProjectId}/{TaskId}",[\App\Http\Controllers\StatusController::class, "getStatus"]);
Route::post("set-status",[\App\Http\Controllers\StatusController::class, "setStatus"]);
Route::post("filter-status/{ProjectId}/{Task}/{StatusId}",[\App\Http\Controllers\StatusController::class, "statusFilterProjectOrTask"]);

/*NotificationController*/
Route::post("notifications",[\App\Http\Controllers\NotificationController::class, "Notifications"]);
Route::post("get-manager-notification",[\App\Http\Controllers\NotificationController::class, "getManagerNotification"]);



















