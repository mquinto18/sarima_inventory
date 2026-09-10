@extends('layouts.app')

@push('styles')
<style>
    .form-input-group input.is-invalid,
    .form-input-group select.is-invalid {
        border-color: var(--color-danger);
        background: var(--color-danger-bg);
    }

    .form-input-group input.is-invalid:focus,
    .form-input-group select.is-invalid:focus {
        border-color: var(--color-danger);
        box-shadow: 0 0 0 3px var(--color-danger-bg);
    }

    .field-error {
        display: block;
        margin-top: 6px;
        color: var(--color-danger-text);
        font-size: 0.88rem;
        font-weight: 500;
    }
</style>
@endpush

@section('content')
<div class="page-shell">
    @php
        $adminCount = $users->where('role', 'admin')->count();
        $managerCount = $users->where('role', 'manager')->count();
        $staffCount = $users->where('role', 'staff')->count();
    @endphp
    <div class="dashboard-hero">
        <div>
            <div class="dashboard-hero-greeting">Account Management</div>
            <div class="dashboard-hero-sub">
                {{ $users->count() }} user{{ $users->count() === 1 ? '' : 's' }} — {{ $adminCount }} admin{{ $adminCount === 1 ? '' : 's' }}, {{ $managerCount }} manager{{ $managerCount === 1 ? '' : 's' }}, {{ $staffCount }} staff.
            </div>
        </div>
        <div class="dashboard-hero-actions">
            <a href="javascript:void(0)" onclick="showAddUserModal()" class="btn-action edit">+ Add New User</a>
        </div>
    </div>

    {{-- Flash messages and validation errors are surfaced as a toast by
         layouts/app.blade.php; the modal below also shows per-field errors. --}}
    <div class="data-table-container">
        <div style="font-weight: 700; font-size: 1.15rem; color: var(--color-text); padding: 0 24px 16px;">User Accounts
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th style="text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <span class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                <span style="font-weight: 500; color: var(--color-text);">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td style="color: var(--color-text-muted);">{{ $user->email }}</td>
                        <td>
                            <span class="role-{{ $user->role }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td style="color: var(--color-text-muted);">{{ $user->created_at->format('M d, Y') }}</td>
                        <td style="text-align: center; white-space: nowrap;">
                            <button onclick="editUser('{{ $user->id }}')" class="btn-action edit">Edit</button>
                            @if($user->id !== Auth::id())
                                <button onclick="archiveUser('{{ $user->id }}', '{{ addslashes($user->name) }}')"
                                    class="btn-action delete">Archive</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


<!-- Add/Edit User Modal -->
<div id="userModal"
    class="modal-overlay" style="display: none;">
    <div class="modal-card" style="padding: 36px 32px; max-width: 500px; width: 90%;">
        <h2 style="margin-top: 0; margin-bottom: 24px; color: var(--color-primary); font-weight: 800; font-size: 1.5rem;">Add New
            User</h2>
        <form id="userForm" method="POST" action="/account-management/users"
            onsubmit="return handleUserFormSubmit(event)">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <input type="hidden" name="user_id" id="userId">

            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 7px; color: var(--color-text); font-weight: 700;">Name</label>
                <div class="form-input-group">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="4" /><path d="M4 20c0-3.3 3.6-6 8-6s8 2.7 8 6" stroke-linecap="round" />
                    </svg>
                    <input type="text" name="name" id="userName" value="{{ old('name') }}"
                        class="@error('name') is-invalid @enderror" required>
                </div>
                @error('name')
                    <small class="field-error">{{ $message }}</small>
                @enderror
            </div>

            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 7px; color: var(--color-text); font-weight: 700;">Email</label>
                <div class="form-input-group">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 7l9 6 9-6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <input type="email" name="email" id="userEmail" value="{{ old('email') }}"
                        class="@error('email') is-invalid @enderror" required>
                </div>
                @error('email')
                    <small class="field-error">{{ $message }}</small>
                @enderror
            </div>

            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 7px; color: var(--color-text); font-weight: 700;">Password <span
                        style="color: var(--color-danger); font-size: 0.95em;">(min. 8 characters)</span></label>
                <div class="form-input-group">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="4" y="10" width="16" height="10" rx="2" /><path d="M8 10V7a4 4 0 1 1 8 0v3" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <input type="password" name="password" id="userPassword" minlength="8"
                        class="@error('password') is-invalid @enderror">
                </div>
                @error('password')
                    <small class="field-error">{{ $message }}</small>
                @enderror
                <small style="color: var(--color-danger);">* Password must be at least 8 characters.<br></small>
                <small style="color: var(--color-text-muted);">Leave blank to keep current password (when editing)</small>
            </div>

            <div style="margin-bottom: 28px;">
                <label style="display: block; margin-bottom: 7px; color: var(--color-text); font-weight: 700;">Role</label>
                <div class="form-input-group">
                    <svg class="form-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke-linecap="round" />
                    </svg>
                    <select name="role" id="userRole" class="@error('role') is-invalid @enderror" required>
                        <option value="staff" @selected(old('role') === 'staff')>Staff</option>
                        <option value="manager" @selected(old('role') === 'manager')>Manager</option>
                        <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                    </select>
                </div>
                @error('role')
                    <small class="field-error">{{ $message }}</small>
                @enderror
            </div>

            <div style="display: flex; gap: 14px; justify-content: flex-end;">
                <button type="button" onclick="closeUserModal()" class="btn-action"
                    style="background: #6c757d;">Cancel</button>
                <button type="submit" class="btn-action edit">Save
                    User</button>
            </div>
        </form>
    </div>
</div>

<script>
    function showAddUserModal() {
        document.getElementById('userModal').style.display = 'flex';
        document.getElementById('userForm').reset();
        document.getElementById('userForm').action = '/account-management/users';
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('userId').value = '';
        document.querySelector('#userModal h2').textContent = 'Add New User';
        document.getElementById('userPassword').required = true;
        document.getElementById('userRole').disabled = false;
    }

    function editUser(userId) {
        // Fetch user data via AJAX
        fetch('/account-management/users/' + userId + '/edit')
            .then(response => response.json())
            .then(user => {
                document.getElementById('userModal').style.display = 'flex';
                document.getElementById('userForm').action = '/account-management/users/' + userId;
                document.getElementById('formMethod').value = 'PUT';
                document.getElementById('userId').value = user.id;
                document.getElementById('userName').value = user.name;
                document.getElementById('userEmail').value = user.email;
                document.getElementById('userRole').value = user.role;
                document.getElementById('userRole').disabled = false;
                document.getElementById('userPassword').value = '';
                document.querySelector('#userModal h2').textContent = 'Edit User';
                document.getElementById('userPassword').required = false;
            })
            .catch(error => {
                console.error('Error fetching user data:', error);
                showToast('Error loading user data', 'error');
            });
    }

    function handleUserFormSubmit(event) {
        // Show loading feedback on the submit button before the page navigates
        var submitBtn = document.querySelector('#userForm button[type="submit"]');
        setButtonLoading(submitBtn, true, 'Saving...');
        // Let the form submit as normal
        return true;
    }

    function archiveUser(userId, userName) {
        confirmDialog('Archive "' + userName + '"? They will no longer be able to sign in. '
            + 'Their history is kept, and you can restore the account from Settings.', {
            title: 'Archive user',
            confirmText: 'Archive'
        }).then(function (confirmed) {
            if (!confirmed) return;

            // Submit delete form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/account-management/users/' + userId;

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';

            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            form.submit();
        });
    }

    function closeUserModal() {
        document.getElementById('userModal').style.display = 'none';
    }

    // Close modal when clicking outside
    document.getElementById('userModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeUserModal();
        }
    });

    // A rejected save (duplicate email, short password, …) redirects back here.
    // Reopen the modal on the offending record with the submitted values still
    // in place — otherwise the user loses everything they typed and the page
    // just appears to have reloaded for no reason.
    @if($errors->any())
        (function () {
            var rejectedUserId = @json(old('user_id'));
            var form = document.getElementById('userForm');

            if (rejectedUserId) {
                form.action = '/account-management/users/' + rejectedUserId;
                document.getElementById('formMethod').value = 'PUT';
                document.getElementById('userId').value = rejectedUserId;
                document.querySelector('#userModal h2').textContent = 'Edit User';
                document.getElementById('userPassword').required = false;
            } else {
                document.getElementById('userPassword').required = true;
            }

            // Deliberately no form.reset() here: it would wipe the old() values.
            document.getElementById('userModal').style.display = 'flex';

            var firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) firstInvalid.focus({ preventScroll: true });
        })();
    @endif
</script>
@endsection
