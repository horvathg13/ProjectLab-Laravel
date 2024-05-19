<?php

namespace App\Http\Middleware;

use App\Traits\PermissionTrait;
use Closure;
use Illuminate\Http\Request;
use PHPUnit\Util\Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProjectParticipantAuthorityMiddleware
{
    use PermissionTrait;
    public function handle(Request $request, Closure $next)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles()->pluck('role_name')->toArray();
        $haveEmployeeRole=in_array("Employee", $roles);
        $haveAdminRole=in_array("Admin", $roles);
        $isProjectParticipant=$this->checkProjectParticipant($request->projectId, $user->id);
        $isProjectManager = $this->checkProjectManagerRole($request->projectId, $user->id);
        if(($haveEmployeeRole && $isProjectParticipant) || $haveAdminRole || $isProjectManager){
            $request->merge([
                "haveAdminRole"=>$haveAdminRole,
                "isProjectManager"=>$isProjectManager ?? false,
                "isProjectParticipant"=>$isProjectParticipant ?? false,
            ]);
            return $next($request);
        }else{
            throw new Exception('Denied');
        }
    }
}
