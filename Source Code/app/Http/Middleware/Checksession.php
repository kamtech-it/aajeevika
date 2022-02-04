<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\Documents;
use Illuminate\Support\Facades\Crypt;

class Checksession
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
      
        $user = Auth::user();

        if (Auth::user()) {
            if(Auth::user()->role_id == 4 || Auth::user()->role_id == 5) {
                // echo "asdf";
                // die;
                //Auth::logout();
                return redirect('/admin');
            }

            if (Auth::user()->role_id == 2 || Auth::user()->role_id == 3) {
                if (Auth::user()->is_otp_verified == 0) {
                    return redirect('/verifyotp/signup');
                }

                if (Auth::user()->is_document_added == 0) {
                    return redirect('/add_document');
                }
            }
            if (Auth::user()->role_id == 1) {
                if (Auth::user()->is_otp_verified == 0) {
                    return redirect('/verifyotp/signup');
                }
            }

            $document = Documents::where('user_id', Auth::user()->id)->first();

            if ($user->role_id == 2) {
                if ($user->is_document_added == 1) {
                    if ($document->is_adhar_verify == 0) {
                        return redirect('/profile')->withErrors('Please Verify Adhar Card');
                    }
                }
            }
            if ($user->role_id == 3) {
                if ($user->is_document_added == 1) {
                    if (($document->is_adhar_verify == 0) || ($document->is_pan_verify == 0) || ($document->is_brn_verify == 0)) {
                        return redirect('/profile')->withErrors('We have recieved your documents, Please wait till we verify them.');
                    }
                }
            }
        }
        

        return $next($request);
    }
}
