<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

use App\RolePermission;
use App\Permission;

class Verifyadmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::user()) {
            if (Auth::user()->role_id !== "4" && Auth::user()->role_id !== "5") {

                // if(Auth::user()->is_blocked_byadmin == 1) {

                //     Auth::logout();

                //     $request->session()->invalidate();

                //     $request->session()->regenerateToken();
                //     die("id");
                //     return redirect('login');
                // }else{
                //     die("check");
                // }

                return redirect('/');
                //abort(403, 'Unauthorized action.');
            }
            

         


            
        }else{
            return redirect('/'); 
        }

        return $next($request);
    }
}
