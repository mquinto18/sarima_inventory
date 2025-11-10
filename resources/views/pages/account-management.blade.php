@include('components.topheader')
@include('components.sidebar')

<div style="margin-left: 220px; padding: 40px; padding-top: 90px; background: #fafbfc; min-height: 100vh;">
    <h1 style="margin-bottom: 0.25em;">Account Management</h1>
    <p style="margin-top: 0; color: #666;">Manage user accounts and access permissions</p>

    <!-- Add New User Button -->
    <div style="margin-bottom: 24px;">
        <button onclick="showAddUserModal()" style="background: #030213; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-size: 1rem; cursor: pointer; font-weight: 500;">
            + Add New User
        </button>
    </div>

    <!-- Users Table -->
    <div style="background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
        <div style="font-weight: 600; font-size: 1.2rem; margin-bottom: 20px; color: #23272f;">User Accounts</div>

        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid #e5e7eb;">
                    <th style="text-align: left; padding: 12px; color: #6b7280; font-weight: 600;">Name</th>
                    <th style="text-align: left; padding: 12px; color: #6b7280; font-weight: 600;">Email</th>
                    <th style="text-align: left; padding: 12px; color: #6b7280; font-weight: 600;">Role</th>
                    <th style="text-align: left; padding: 12px; color: #6b7280; font-weight: 600;">Created</th>
                    <th style="text-align: center; padding: 12px; color: #6b7280; font-weight: 600;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 16px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="width: 40px; height: 40px; border-radius: 50%; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </span>
                            <span style="font-weight: 500; color: #23272f;">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td style="padding: 16px; color: #6b7280;">{{ $user->email }}</td>
                    <td style="padding: 16px;">
                        @php
                        $roleStyles = [
                        'admin' => 'background: #fee2e2; color: #991b1b;',
                        'manager' => 'background: #fef3c7; color: #92400e;',
                        'staff' => 'background: #dbeafe; color: #1e40af;'
                        ];
                        @endphp
                        <span style="padding: 6px 12px; border-radius: 6px; font-size: 0.875rem; font-weight: 500; {{ $roleStyles[$user->role] ?? '' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td style="padding: 16px; color: #6b7280;">{{ $user->created_at->format('M d, Y') }}</td>
                    <td style="padding: 16px; text-align: center;">
                        <button onclick="editUser({{ $user->id }})" style="background: #f3f4f6; border: none; padding: 8px 16px; border-radius: 6px; margin-right: 8px; cursor: pointer; color: #4b5563; font-size: 0.875rem;" onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                            Edit
                        </button>
                        @if($user->id !== Auth::id())
                        <button onclick="deleteUser({{ $user->id }})" style="background: #fee2e2; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; color: #991b1b; font-size: 0.875rem;" onmouseover="this.style.background='#fecaca'" onmouseout="this.style.background='#fee2e2'">
                            Delete
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div id="userModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; padding: 32px; max-width: 500px; width: 90%;">
        <h2 style="margin-top: 0; margin-bottom: 24px; color: #23272f;">Add New User</h2>

        <form id="userForm" method="POST" action="/account-management/users">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <input type="hidden" name="user_id" id="userId">

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500;">Name</label>
                <input type="text" name="name" id="userName" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500;">Email</label>
                <input type="email" name="email" id="userEmail" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500;">Password</label>
                <input type="password" name="password" id="userPassword" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;">
                <small style="color: #6b7280;">Leave blank to keep current password (when editing)</small>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 500;">Role</label>
                <select name="role" id="userRole" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;">
                    <option value="staff">Staff</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closeUserModal()" style="background: #f3f4f6; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; color: #4b5563; font-size: 1rem;">
                    Cancel
                </button>
                <button type="submit" style="background: #030213; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: 500;">
                    Save User
                </button>
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
                document.getElementById('userPassword').value = '';
                document.querySelector('#userModal h2').textContent = 'Edit User';
                document.getElementById('userPassword').required = false;
            })
            .catch(error => {
                console.error('Error fetching user data:', error);
                alert('Error loading user data');
            });
    }

    function deleteUser(userId) {
        if (confirm('Are you sure you want to delete this user?')) {
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
        }
    }

    function closeUserModal() {
        document.getElementById('userModal').style.display = 'none';
    }

    // Close modal when clicking outside
    document.getElementById('userModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeUserModal();
        }
    });
</script>