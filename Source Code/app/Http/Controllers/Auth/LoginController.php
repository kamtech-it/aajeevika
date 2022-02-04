<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Session;

// use App\User;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->redirectTo = url()->previous();
        $this->middleware('guest')->except('logout');
    }

    // use AuthenticatesUsers;
    use AuthenticatesUsers {
        logout as performLogout;
    }

    public function logout(Request $request)
    {
        // Auth::logout();
        //Session::flush();
        $this->performLogout($request);
        // return redirect()->route('login');
        return redirect('/');
    }


    /**
     * Where to redirect users after login.
     *
     * @var string
     */

    // protected $redirectTo = '/home';

    protected function redirectTo()
    {
        if (Auth::user()->is_blocked_byadmin == 1) {
            Auth::logout();
            Session::flash('message', 'Your account has been blocked by admin, Please contact your admin for more information.');
            Session::flash('alert-class', 'alert-danger');
            return 'login';
        }

        if ((Auth::user()->role_id == 1) || (Auth::user()->role_id== 2) || (Auth::user()->role_id== 3)) {
            return '/';
        } elseif ((Auth::user()->role_id== 4) || (Auth::user()->role_id== 5)) {
            return '/admin';
        }
    }




    /**
       * Get the needed authorization credentials from the request.
       *
       * @param  \Illuminate\Http\Request  $request
       * @return array
       */


    protected function credentials(Request $request)
    {
        if (is_numeric($request->get('email'))) {
            return ['mobile'=>$request->get('email'),'password'=>$request->get('password')];
        } elseif (filter_var($request->get('email'), FILTER_VALIDATE_EMAIL)) {
            return ['email' => $request->get('email'), 'password'=>$request->get('password')];
        }
    }


    public function showLoginForm(Request $request)
    {
        $input = $request->all();
        if (isset($input['type']) && isset($input['id'])) {
            $type = $request->query('type');
            $id  = $request->query('id');
            $link =  url($type).'/'.$id;
            session(['url.intended' => $link]);
        }
        return view('auth.login',['input'=>$input]);
    }

    protected function authenticated(Request $request, $user)
    {
        $this->redirectTo = session()->get('url.intended');
    }
}
