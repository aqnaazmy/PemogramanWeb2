<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Display the registration form.
     */
    public function register()
    {
        return view('auth.register');
    }
    
    /**
     * Handle registration request.
     */
    public function registerSave(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->route('register')
                             ->withErrors($validator)
                             ->withInput();
        }
  
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'level' => 'Admin'
        ]);
  
        return redirect()->route('dashboard')->with('success', 'Registration successful!');
    }
    
    public function login()
    {
        return view('auth.login');
    }

    public function loginAction(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->route('login')
                             ->withErrors($validator)
                             ->withInput();
        }
  
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => trans('auth.failed')
            ]);
        }
  
        $request->session()->regenerate();
  
        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
  
        $request->session()->invalidate();
        $request->session()->regenerateToken();
  
        return redirect('/');
    }

    public function profile()
    {
        return view('profile');
    }
}
