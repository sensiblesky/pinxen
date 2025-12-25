<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\LoginActivity;
use App\Models\Timezone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load(['language', 'timezone']);
        $languages = Language::all();
        $timezones = Timezone::all();
        
        // Get current session information
        $currentSession = $this->getCurrentSession($request);
        
        // Get all active sessions for the user
        $activeSessions = $this->getActiveSessions($user);
        
        // Get login activities (last 50)
        $loginActivities = LoginActivity::where('user_id', $user->id)
            ->recent(50)
            ->get();
        
        return view('shared.profile.edit', [
            'user' => $user,
            'languages' => $languages,
            'timezones' => $timezones,
            'currentSession' => $currentSession,
            'activeSessions' => $activeSessions,
            'loginActivities' => $loginActivities,
        ]);
    }

    /**
     * Get all active sessions for the user.
     */
    private function getActiveSessions($user)
    {
        // First, mark any login activities as inactive if their sessions no longer exist
        $this->markExpiredSessionsAsInactive($user);
        
        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('last_activity', '>', now()->subMinutes(config('session.lifetime', 120))->timestamp)
            ->orderBy('last_activity', 'desc')
            ->get();

        $activeSessions = [];
        foreach ($sessions as $session) {
            $deviceInfo = $this->parseUserAgent($session->user_agent ?? '');
            
            // Find login activity by session_id (more accurate)
            $loginActivity = LoginActivity::where('user_id', $user->id)
                ->where('session_id', $session->id)
                ->where('is_active', true)
                ->whereNull('logged_out_at')
                ->first();
            
            // Fallback: if no match by session_id, try by IP and time
            if (!$loginActivity) {
                $loginActivity = LoginActivity::where('user_id', $user->id)
                    ->where('ip_address', $session->ip_address)
                    ->where('is_active', true)
                    ->whereNull('logged_out_at')
                    ->where('logged_in_at', '<=', date('Y-m-d H:i:s', $session->last_activity))
                    ->latest('logged_in_at')
                    ->first();
            }

            $activeSessions[] = [
                'session_id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'device_type' => $deviceInfo['device_type'],
                'browser' => $deviceInfo['browser'],
                'platform' => $deviceInfo['platform'],
                'last_activity' => date('Y-m-d H:i:s', $session->last_activity),
                'logged_in_at' => $loginActivity ? $loginActivity->logged_in_at->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', $session->last_activity - config('session.lifetime', 120) * 60),
                'login_activity_id' => $loginActivity ? $loginActivity->id : null,
                'is_current' => $session->id === request()->session()->getId(),
            ];
        }

        return $activeSessions;
    }
    
    /**
     * Mark login activities as inactive if their sessions no longer exist.
     */
    private function markExpiredSessionsAsInactive($user)
    {
        // Get all active login activities for this user
        $activeActivities = LoginActivity::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereNull('logged_out_at')
            ->whereNotNull('session_id')
            ->get();
        
        // Get all existing session IDs from the sessions table
        $existingSessionIds = DB::table('sessions')
            ->where('user_id', $user->id)
            ->pluck('id')
            ->toArray();
        
        // Mark activities as inactive if their session no longer exists
        foreach ($activeActivities as $activity) {
            if (!in_array($activity->session_id, $existingSessionIds)) {
                $activity->update([
                    'is_active' => false,
                    'logged_out_at' => now(),
                ]);
            }
        }
    }

    /**
     * Terminate a session.
     */
    public function terminateSession(Request $request, $sessionId)
    {
        $user = $request->user();
        
        // Prevent terminating current session
        if ($sessionId === $request->session()->getId()) {
            return redirect()->route('profile.edit')
                ->with('error', 'You cannot terminate your current session from here. Please logout instead.');
        }

        // Get session info before deleting
        $session = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            return redirect()->route('profile.edit')
                ->with('error', 'Session not found or already terminated.');
        }

        // Update login activity if exists (prefer session_id for more accurate matching)
        $loginActivity = LoginActivity::where('user_id', $user->id)
            ->where('session_id', $sessionId)
            ->where('is_active', true)
            ->whereNull('logged_out_at')
            ->first();
        
        // Fallback to IP address matching if session_id doesn't match
        if (!$loginActivity) {
            $loginActivity = LoginActivity::where('user_id', $user->id)
                ->where('ip_address', $session->ip_address)
                ->where('is_active', true)
                ->whereNull('logged_out_at')
                ->latest('logged_in_at')
                ->first();
        }

        if ($loginActivity) {
            $loginActivity->update([
                'logged_out_at' => now(),
                'is_active' => false,
                'action' => 'logout',
            ]);
        }

        // Delete the session from database
        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->delete();

        return redirect()->route('profile.edit')
            ->with('success', 'Session terminated successfully.');
    }

    /**
     * Get current session information.
     */
    private function getCurrentSession(Request $request)
    {
        $sessionId = $request->session()->getId();
        $userAgent = $request->userAgent();
        $deviceInfo = $this->parseUserAgent($userAgent);
        
        // Get session from database
        $session = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', Auth::id())
            ->first();
        
        if ($session) {
            return [
                'session_id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'device_type' => $deviceInfo['device_type'],
                'browser' => $deviceInfo['browser'],
                'platform' => $deviceInfo['platform'],
                'last_activity' => date('Y-m-d H:i:s', $session->last_activity),
                'logged_in_at' => date('Y-m-d H:i:s', $session->last_activity - config('session.lifetime', 120) * 60),
            ];
        }
        
        return [
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'platform' => $deviceInfo['platform'],
            'last_activity' => now()->format('Y-m-d H:i:s'),
            'logged_in_at' => now()->subMinutes(config('session.lifetime', 120))->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Parse user agent to extract device information.
     */
    private function parseUserAgent($userAgent)
    {
        $deviceType = 'desktop';
        $browser = 'Unknown';
        $platform = 'Unknown';

        // Detect device type
        if (preg_match('/mobile|android|iphone|ipad|ipod|blackberry|iemobile|opera mini/i', $userAgent)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            $deviceType = 'tablet';
        }

        // Detect browser
        if (preg_match('/chrome/i', $userAgent) && !preg_match('/edg/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/edg/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/opera|opr/i', $userAgent)) {
            $browser = 'Opera';
        }

        // Detect platform
        if (preg_match('/windows/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $platform = 'iOS';
        }

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'platform' => $platform,
        ];
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone,' . $user->id],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,gif', 'max:5120'], // 5MB max
            'remove_avatar' => ['nullable', 'string', 'in:0,1'],
            'language_id' => ['nullable', 'exists:languages,id'],
            'timezone_id' => ['nullable', 'exists:timezones,id'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('profile.edit')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar) {
                try {
                    Storage::disk('public')->delete($user->avatar);
                } catch (\Exception $e) {
                    // Log error but continue
                }
            }

            // Store new avatar
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $avatarPath;
        }

        // Handle avatar removal
        if (isset($validated['remove_avatar']) && ($validated['remove_avatar'] === '1' || $validated['remove_avatar'] === 1 || $validated['remove_avatar'] === true || $validated['remove_avatar'] === 'true')) {
            if ($user->avatar) {
                try {
                    Storage::disk('public')->delete($user->avatar);
                } catch (\Exception $e) {
                    // Log error but continue
                }
            }
            $validated['avatar'] = null;
        } else {
            unset($validated['remove_avatar']);
        }

        // Handle email change
        if ($user->email !== $validated['email']) {
            $user->email_verified_at = null;
            
            // Invalidate all email verification OTPs for old email
            $otpService = app(\App\Services\OtpService::class);
            $otpService->invalidateOtps($user, \App\Services\OtpService::TYPE_EMAIL_VERIFICATION, $user->email);
        }

        // Update user
        $user->fill($validated);
        $user->save();

        return Redirect::route('profile.edit')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
