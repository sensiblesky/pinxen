<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('panel.users.index');
    }

    /**
     * Get users data for DataTables (server-side processing).
     */
    public function getUsersData(Request $request)
    {
        try {
            Log::info('DataTables request received', [
                'draw' => $request->input('draw'),
                'start' => $request->input('start'),
                'length' => $request->input('length'),
            ]);
            
            // Start with base query - apply deleted filter if specified
            if ($request->filled('filter_deleted')) {
                $query = User::where('is_deleted', $request->filter_deleted);
            } else {
                // Default: show only non-deleted users
                $query = User::notDeleted();
            }

            // Apply filters
            if ($request->filled('filter_email')) {
                $query->where('email', 'like', '%' . $request->filter_email . '%');
            }

            if ($request->filled('filter_phone')) {
                $query->where('phone', 'like', '%' . $request->filter_phone . '%');
            }

            if ($request->filled('filter_role')) {
                $query->where('role', $request->filter_role);
            }

            if ($request->filled('filter_status')) {
                $query->where('is_active', $request->filter_status);
            }

            // Global search
            $searchValue = $request->input('search.value');
            if (!empty($searchValue)) {
                $query->where(function($q) use ($searchValue) {
                    $q->where('name', 'like', '%' . $searchValue . '%')
                      ->orWhere('email', 'like', '%' . $searchValue . '%')
                      ->orWhere('phone', 'like', '%' . $searchValue . '%');
                });
            }

            // Get total count before pagination
            $totalRecords = $query->count();

            // Apply ordering
            // Column mapping: 0=row_number, 1=name, 2=email, 3=phone, 4=role_badge, 5=status_badge, 6=created_at, 7=actions
            $orderColumn = $request->input('order.0.column', 6);
            $orderDir = $request->input('order.0.dir', 'desc');
            
            $columnMap = [
                1 => 'name',
                2 => 'email',
                3 => 'phone',
                4 => 'role',
                5 => 'is_active',
                6 => 'created_at',
            ];
            
            $orderBy = $columnMap[$orderColumn] ?? 'created_at';
            
            $query->orderBy($orderBy, $orderDir);

            // Apply pagination
            $start = $request->input('start', 0);
            $length = $request->input('length', 100);
            $users = $query->skip($start)->take($length)->get();

            // Format data for DataTables
            $data = [];
            $rowNumber = $start + 1;
            
            foreach ($users as $user) {
                // Build actions HTML directly to avoid view rendering issues
                $csrfToken = csrf_token();
                $actionsHtml = '<div class="btn-list">
                    <a href="' . route('panel.users.show', $user->uid) . '" class="btn btn-sm btn-info btn-wave" data-bs-toggle="tooltip" title="View">
                        <i class="ri-eye-line"></i>
                    </a>';
                
                // Show delete button if user is not deleted, show restore button if deleted
                if (!$user->is_deleted) {
                    $actionsHtml .= '<form action="' . route('panel.users.destroy', $user->uid) . '" method="POST" class="d-inline delete-user-form" data-user-name="' . htmlspecialchars($user->name, ENT_QUOTES) . '">
                        <input type="hidden" name="_token" value="' . $csrfToken . '">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="button" class="btn btn-sm btn-danger btn-wave delete-user-btn" data-bs-toggle="tooltip" title="Delete">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </form>';
                } else {
                    $actionsHtml .= '<form action="' . route('panel.users.restore', $user->uid) . '" method="POST" class="d-inline restore-user-form" data-user-name="' . htmlspecialchars($user->name, ENT_QUOTES) . '">
                        <input type="hidden" name="_token" value="' . $csrfToken . '">
                        <button type="button" class="btn btn-sm btn-success btn-wave restore-user-btn" data-bs-toggle="tooltip" title="Restore">
                            <i class="ri-restart-line"></i>
                        </button>
                    </form>';
                }
                
                $actionsHtml .= '</div>';
                
                $data[] = [
                    'row_number' => $rowNumber++,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? 'N/A',
                    'role' => $user->role,
                    'role_badge' => $user->role == 1 
                        ? '<span class="badge bg-success">Admin</span>' 
                        : '<span class="badge bg-secondary">User</span>',
                    'status' => $user->is_active,
                    'status_badge' => $user->is_deleted 
                        ? '<span class="badge bg-dark">Deleted</span>' 
                        : ($user->is_active 
                            ? '<span class="badge bg-success">Active</span>' 
                            : '<span class="badge bg-danger">Inactive</span>'),
                    'created_at' => $user->created_at->format('Y-m-d H:i'),
                    'uid' => $user->uid,
                    'actions' => $actionsHtml,
                ];
            }

            // Calculate total records based on deleted filter
            if ($request->filled('filter_deleted')) {
                $totalRecordsCount = User::where('is_deleted', $request->filter_deleted)->count();
            } else {
                $totalRecordsCount = User::notDeleted()->count();
            }
            
            $response = response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => $totalRecordsCount,
                'recordsFiltered' => $totalRecords,
                'data' => $data,
            ]);
            
            $response->header('Content-Type', 'application/json');
            
            return $response;
        } catch (\Exception $e) {
            \Log::error('DataTables Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while loading data. Please check the logs.',
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $languages = \App\Models\Language::where('is_active', true)->get();
        $timezones = \App\Models\Timezone::where('is_active', true)->get();
        return view('panel.users.create', compact('languages', 'timezones'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'integer', 'in:1,2'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'language_id' => ['nullable', 'exists:languages,id'],
            'timezone_id' => ['nullable', 'exists:timezones,id'],
            'notify_in_app' => ['boolean'],
            'notify_email' => ['boolean'],
            'notify_push' => ['boolean'],
            'notify_sms' => ['boolean'],
            'require_password_verification' => ['boolean'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => !empty($validated['phone']) ? $validated['phone'] : null,
            'language_id' => $validated['language_id'] ?? null,
            'timezone_id' => $validated['timezone_id'] ?? null,
            'notify_in_app' => $request->has('notify_in_app'),
            'notify_email' => $request->has('notify_email'),
            'notify_push' => $request->has('notify_push'),
            'notify_sms' => $request->has('notify_sms'),
            'require_password_verification' => $request->has('require_password_verification'),
        ]);

        return redirect()->route('panel.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): View
    {
        // Eager load relationships to avoid N+1 queries
        $user->load(['language', 'timezone']);
        
        $languages = \App\Models\Language::where('is_active', true)->get();
        $timezones = \App\Models\Timezone::where('is_active', true)->get();
        return view('panel.users.show', compact('user', 'languages', 'timezones'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        $languages = \App\Models\Language::where('is_active', true)->get();
        $timezones = \App\Models\Timezone::where('is_active', true)->get();
        return view('panel.users.edit', compact('user', 'languages', 'timezones'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['required', 'integer', 'in:1,2'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone,'.$user->id],
            'language_id' => ['nullable', 'exists:languages,id'],
            'timezone_id' => ['nullable', 'exists:timezones,id'],
            'avatar' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,gif',
                'mimetypes:image/jpeg,image/png,image/gif',
                'max:5120', // 5MB max in kilobytes
            ],
            'remove_avatar' => ['nullable', 'string', 'in:0,1'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->phone = !empty($validated['phone']) ? $validated['phone'] : null;
        $user->language_id = $validated['language_id'] ?? null;
        $user->timezone_id = $validated['timezone_id'] ?? null;
        
        // Handle avatar upload or removal
        $removeAvatarValue = $request->input('remove_avatar');
        $removeAvatar = $removeAvatarValue === '1' || $removeAvatarValue === 1 || $removeAvatarValue === true || $removeAvatarValue === 'true';
        
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            
            // Additional security validations
            // 1. Verify file size (5MB = 5242880 bytes)
            if ($file->getSize() > 5242880) {
                return redirect()->route('panel.users.show', $user)
                    ->with('error', 'File size exceeds 5MB limit.');
            }
            
            // 2. Verify it's actually an image using getimagesize
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo === false) {
                return redirect()->route('panel.users.show', $user)
                    ->with('error', 'Invalid image file. File is not a valid image.');
            }
            
            // 3. Verify MIME type matches allowed types
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
            $detectedMime = $imageInfo['mime'];
            if (!in_array($detectedMime, $allowedMimes)) {
                return redirect()->route('panel.users.show', $user)
                    ->with('error', 'Invalid image type. Only JPEG, PNG, and GIF are allowed.');
            }
            
            // 4. Sanitize and generate secure filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $sanitizedExtension = strtolower($extension);
            
            // Only allow safe extensions
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($sanitizedExtension, $allowedExtensions)) {
                return redirect()->route('panel.users.show', $user)
                    ->with('error', 'Invalid file extension.');
            }
            
            // Generate secure random filename
            $secureFileName = \Illuminate\Support\Str::random(40) . '.' . $sanitizedExtension;
            
            // Delete old avatar if exists
            if ($user->avatar) {
                $oldAvatarPath = $user->avatar;
                try {
                    if (Storage::disk('public')->exists($oldAvatarPath)) {
                        Storage::disk('public')->delete($oldAvatarPath);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to delete old avatar: ' . $e->getMessage());
                }
            }
            
            // Store new avatar with secure filename
            $avatarPath = $file->storeAs('avatars', $secureFileName, 'public');
            $user->avatar = $avatarPath;
        } elseif ($removeAvatar) {
            // Remove avatar if requested
            if ($user->avatar) {
                $oldAvatarPath = $user->avatar;
                // Try to delete from public disk
                try {
                    if (Storage::disk('public')->exists($oldAvatarPath)) {
                        Storage::disk('public')->delete($oldAvatarPath);
                    }
                    // Also try alternative path format
                    $alternativePath = str_replace('avatars/', '', $oldAvatarPath);
                    if (Storage::disk('public')->exists($alternativePath)) {
                        Storage::disk('public')->delete($alternativePath);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to delete avatar file: ' . $e->getMessage(), [
                        'avatar_path' => $oldAvatarPath,
                        'user_id' => $user->id
                    ]);
                }
            }
            // Always set avatar to null in database, even if file deletion fails
            $user->avatar = null;
        }
        
        $user->save();

        return redirect()->route('panel.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Deactivate or activate the specified user.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        // Prevent deactivating yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('panel.users.show', $user)
                ->with('error', 'You cannot deactivate your own account.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $message = $user->is_active ? 'User activated successfully.' : 'User deactivated successfully.';
        
        return redirect()->route('panel.users.show', $user)
            ->with('success', $message);
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(User $user): RedirectResponse
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('panel.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        // Soft delete - set is_deleted to true
        $user->is_deleted = true;
        $user->save();

        return redirect()->route('panel.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Restore a deleted user.
     */
    public function restore(User $user): RedirectResponse
    {
        // Check if user is actually deleted
        if (!$user->is_deleted) {
            return redirect()->route('panel.users.index')
                ->with('error', 'User is not deleted.');
        }

        // Restore user - set is_deleted to false
        $user->is_deleted = false;
        $user->save();

        return redirect()->route('panel.users.index')
            ->with('success', 'User restored successfully.');
    }

    /**
     * Update user language and timezone.
     */
    public function updateLanguageTimezone(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'language_id' => ['nullable', 'exists:languages,id'],
            'timezone_id' => ['nullable', 'exists:timezones,id'],
        ]);

        $user->language_id = $validated['language_id'] ?? null;
        $user->timezone_id = $validated['timezone_id'] ?? null;
        $user->save();

        return redirect()->route('panel.users.show', $user)
            ->with('success', 'Language and timezone updated successfully.');
    }
}
