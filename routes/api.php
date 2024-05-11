<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\{
    RegisterController,
    AuthController,
    PermissionController,
    UserController,
    ProjectController,
    TaskController,
    MessageController,
    NotificationController,
    StatusController,
    ButtonController,
};

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
Route::controller(RegisterController::class)->group(function (){
    Route::post("register","register");
    Route::post("createuser","createUser");
    Route::get("findUser/{token} ","findUser");
    Route::post("resetpassword", "resetPassword");
    Route::post("password-reset-manual/{id}","passwordResetManual");
    Route::post("ban-user/{id}","banTheUser");
});


/*AuthController*/
Route::controller(AuthController::class)->group(function () {
    Route::post("login", "login");
    Route::post("logout", "logout");
});

/*PermissionController*/
Route::controller(PermissionController::class)->group(function () {
    Route::post("getUserRole", "getUserRole");
    Route::post("getroles", "getRoles");
});

/*UserController*/
Route::controller(UserController::class)->group(function () {
    Route::post("getusers", "getUsers");
    Route::post("getUserData","getUserData");
    Route::post("getEmployees", "getEmployees");
    Route::post("getManagers","getManagers");
    Route::post("user-to-role", "userToRole");
    Route::post("save-profile-data", "saveProfileData");
});

/*ProjectController*/
Route::controller(ProjectController::class)->group(function () {
    Route::post("createproject", "createProject");
    Route::post("getprojects","getProjects");
    Route::post("projects/{id}", "getProjectById");
    Route::post("getprojectparticipants/{id}", "getProjectParticipants");
    Route::post("createparticipants", "createParticipants");
    Route::post("add-favorite-project", "addFavoriteProject");
    Route::post("remove-favorite-project", "removeFromFavorite");
    Route::post("get-favorite-projects", "getFavoriteProjects");
    Route::post("get-manager-projects", "getManagerProjects");
    Route::post("leave-project", "leaveProject");
});

/*TaskController*/
Route::controller(TaskController::class)->group(function () {
    Route::post("createtask", "createtask");
    Route::post("getpriorities", "getPriorities");
    Route::post("projects/{id}/tasks", "getTasks");
    Route::post("assign-employee-to-task","AssignEmpoyleeToTask");
    Route::post("task-attach-to-myself", "AttachMyself");
    Route::post("getActiveEmployees/{task_id}","getActiveEmployees");
    Route::post("completed", "Completed");
    Route::post("get-my-tasks",  "MyTasks");
    Route::post("get-manager-tasks", "getManagerTasks");
    Route::post("managed-completed-tasks", "managedCompletedTasks");
    Route::post("accept-all-task", "acceptAllTask");
    Route::post("count-of-my-active-tasks", "countOfMyTasks");
    Route::post("accessControlForTasks", "AccessControlForTasks");
});

/*MessageController*/
Route::controller(MessageController::class)->group(function () {
    Route::post("send-message", "sendMessage");
    Route::post("get-messages", "getMessages");
    Route::post("get-unread-messages", "getUnreadMessages");
});

/*ButtonController*/
Route::controller(ButtonController::class)->group(function () {
    Route::post("get-buttons/{projecId}", "getProjectandTaskButtons");
    Route::post("get-users-buttons", "getUsersButton");
});

/*StatusController*/
Route::controller(StatusController::class)->group(function () {
    Route::post("get-status/{ProjectId}/{TaskId}", "getStatus");
    Route::post("set-status", "setStatus");
    Route::post("filter-status/{ProjectId}/{Task}/{StatusId}", "statusFilterProjectOrTask");
});

/*NotificationController*/
Route::controller(NotificationController::class)->group(function () {
    Route::post("notifications", "Notifications");
    Route::post("get-manager-notification", "getManagerNotification");
});



















