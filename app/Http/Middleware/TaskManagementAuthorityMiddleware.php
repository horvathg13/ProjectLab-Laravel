<?php

namespace App\Http\Middleware;

use App\Traits\PermissionTrait;
use Closure;
use Illuminate\Http\Request;
use PHPUnit\Util\Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class TaskManagementAuthorityMiddleware
{
    use PermissionTrait;
    public function handle(Request $request, Closure $next)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles()->pluck('role_name')->toArray();
        $haveAdminRole=in_array("Admin", $roles);
        $haveManagerRole=in_array("Manager", $roles);
        if($request->projectId){
            $isProjectParticipant=$this->checkProjectParticipant($request->projectId, $user->id);
            $isProjectManager = $this->checkProjectManagerRole($request->projectId, $user->id);
        }
        if(($haveManagerRole && ($isProjectManager?? true))|| $haveAdminRole ){
            $request->merge([
                "haveAdminRole"=>$haveAdminRole,
                "haveManagerRole"=>$haveManagerRole,
                "isProjectManager"=>$isProjectManager ?? false,
                "isProjectParticipant"=>$isProjectParticipant ?? false,
            ]);
            return $next($request);
        }else{
            throw new Exception('Denied');
        }
    }
}
