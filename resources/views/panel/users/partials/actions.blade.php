<div class="btn-list">
    <a href="{{ route('panel.users.show', $user->uid) }}" class="btn btn-sm btn-info btn-wave" data-bs-toggle="tooltip" title="View">
        <i class="ri-eye-line"></i>
    </a>
    <form action="{{ route('panel.users.destroy', $user->uid) }}" method="POST" class="d-inline delete-user-form" data-user-name="{{ $user->name }}">
        @csrf
        @method('DELETE')
        <button type="button" class="btn btn-sm btn-danger btn-wave delete-user-btn" data-bs-toggle="tooltip" title="Delete">
            <i class="ri-delete-bin-line"></i>
        </button>
    </form>
</div>


