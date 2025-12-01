<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        // Double-check registration is enabled (middleware should handle this, but this is a backup)
        $registrationEnabled = Setting::get('user_registration_enabled', '1');
        $isDisabled = $registrationEnabled !== '1';
        
        if ($isDisabled) {
            return view('auth.pages.register', [
                'registrationDisabled' => true,
            ])->with('error', 'User registration is currently disabled. Please contact the administrator.');
        }
        
        return view('auth.pages.register', [
            'registrationDisabled' => false,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Double-check registration is enabled
        $registrationEnabled = Setting::get('user_registration_enabled', '1');
        
        if ($registrationEnabled !== '1') {
            throw ValidationException::withMessages([
                'email' => ['User registration is currently disabled. Please contact the administrator.'],
            ]);
        }
        
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Check if force email verification is enabled
        $forceEmailVerification = Setting::get('force_email_verification', '0');
        if ($forceEmailVerification === '1') {
            // Redirect to email verification page
            return redirect()->route('email.verification.show')
                ->with('error', 'Please verify your email address to continue.');
        }

        return redirect(route('dashboard', absolute: false));
    }
}
