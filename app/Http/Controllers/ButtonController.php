<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ButtonController\Exception;
use App\Models\ProjectParticipants;
use App\Models\Projects;
use App\Traits\PermissionTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ButtonController extends Controller
{
    use PermissionTrait;

    public function buttonAuth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "projectId"=>"required|exists:projects,id"
        ]);

        if ($validator->fails()){
            $response=[
                "validatorError"=>$validator->errors()->all(),
            ];
            return response()->json($response, 500);
        }
        $user=JWTAuth::parseToken()->authenticate();
        $isProjectManager = $this->checkProjectManagerRole($request->projectId, $user->id);
        $isProjectParticipant = $this->checkProjectParticipant($request->projectId, $user->id);
        $isAdmin=$this->checkAdmin($user->id);

        $success=[
            "manager"=>$isProjectManager,
            "employee"=>$isProjectParticipant,
            "admin"=>$isAdmin,
        ];
        return response()->json($success,200);
    }


}
