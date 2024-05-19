<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPUnit\Util\Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class ManagementAuthorityMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $roles = $user->roles()->pluck('role_name')->toArray();
        $haveAdminRole=in_array("Admin", $roles);
        $haveManagerRole=in_array("Manager", $roles);
        if($haveManagerRole || $haveAdminRole){
            $request->merge([
                "haveAdminRole"=>$haveAdminRole,
                "haveManagerRole"=>$haveManagerRole
            ]);
            return $next($request);
        }else{
            throw new Exception('Denied');
        }
    }
}
