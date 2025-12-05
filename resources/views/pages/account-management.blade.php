@include('components.topheader')
@include('components.sidebar')


<style>
.dashboard-gradient {
    background: linear-gradient(120deg, #f8fafc 0%, #e0e7ff 100%);
    min-height: 100vh;
}
.dashboard-title {
    font-size: 2.3rem;
    font-weight: 800;
    margin-bottom: 0.2em;
    letter-spacing: -1px;
    color: #18181b;
}
.dashboard-subtitle {
    color: #6366f1;
    font-size: 1.1rem;
    margin-bottom: 2.5rem;
    font-weight: 500;
}
.modern-table-container {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 2px 12px 0 rgba(99,102,241,0.08);
    padding: 24px 0 0 0;
    margin-bottom: 32px;
    overflow: hidden;
}
.modern-table-container .modern-table-header {
    padding-left: 32px;
    padding-top: 8px;
    margin-bottom: 16px;
}
.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 2px 12px 0 rgba(99,102,241,0.06);
    overflow: hidden;
}
.modern-table th, .modern-table td {
    padding: 16px 18px;
    font-size: 1.08rem;
    text-align: left;
    border-bottom: 1.5px solid #f3f4f6;
}
.modern-table thead th {
    background: #f3f4f6;
    color: #6366f1;
    font-weight: 700;
    border-top: none;
    position: sticky;
    top: 0;
    z-index: 2;
}
.modern-table tr:last-child td {
    border-bottom: none;
}
.modern-table tbody tr {
    transition: box-shadow 0.18s, background 0.18s;
}
.modern-table tbody tr:hover {
    background: #eef2ff;
    box-shadow: 0 4px 18px 0 rgba(99,102,241,0.10);
}
.role-admin { background: #fee2e2; color: #991b1b; }
.role-manager { background: #fef3c7; color: #92400e; }
.role-staff { background: #dbeafe; color: #1e40af; }
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e0e7ff 60%, #6366f1 100%);
    color: #4338ca;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
    box-shadow: 0 2px 8px 0 rgba(99,102,241,0.08);
}
.action-btn {
    font-weight: 600;
    border-radius: 12px;
    padding: 8px 20px;
    font-size: 1rem;
    margin: 0 4px;
    border: none;
    box-shadow: 0 2px 8px 0 rgba(99,102,241,0.10);
    transition: background 0.2s, box-shadow 0.2s;
}
.action-btn.edit {
    background: linear-gradient(90deg, #6366f1 0%, #60a5fa 100%);
    color: #fff;
}
.action-btn.edit:hover {
    background: linear-gradient(90deg, #4338ca 0%, #6366f1 100%);
    box-shadow: 0 4px 12px 0 rgba(99,102,241,0.18);
}
.action-btn.delete {
    background: linear-gradient(90deg, #ef4444 0%, #dc3545 100%);
    color: #fff;
}
.action-btn.delete:hover {
    background: linear-gradient(90deg, #b91c1c 0%, #ef4444 100%);
    box-shadow: 0 4px 12px 0 rgba(239,68,68,0.18);
}
</style>

<div class="dashboard-gradient" style="margin-left: 220px; padding: 40px; padding-top: 90px;">
    <div class="dashboard-title">Account Management</div>
    <div class="dashboard-subtitle">Manage user accounts and access permissions</div>
    <div style="margin-bottom: 28px;">
        <button onclick="showAddUserModal()" style="font-weight: 700; padding: 13px 28px; border-radius: 10px; font-size: 1.13rem; background: linear-gradient(90deg, #6366f1 0%, #60a5fa 100%); border: none; color: #fff; box-shadow: 0 2px 8px 0 rgba(99,102,241,0.10); transition: background 0.2s;">+ Add New User</button>
    </div>
    <div class="modern-table-container">
        <div class="modern-table-header" style="font-weight: 700; font-size: 1.15rem; color: #23272f;">User Accounts</div>
        <table class="modern-table">
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
                            <span style="font-weight: 500; color: #23272f;">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td style="color: #6b7280;">{{ $user->email }}</td>
                    <td>
                        <span class="role-{{ $user->role }}" style="padding: 6px 12px; border-radius: 6px; font-size: 0.875rem; font-weight: 500;">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td style="color: #6b7280;">{{ $user->created_at->format('M d, Y') }}</td>
                    <td style="text-align: center; white-space: nowrap;">
                        <button onclick="editUser('{{ $user->id }}')" class="action-btn edit">Edit</button>
                        @if($user->id !== Auth::id())
                        <button onclick="deleteUser('{{ $user->id }}')" class="action-btn delete">Delete</button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


<!-- Modern Add/Edit User Modal -->
<div id="userModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 18px; padding: 36px 32px; max-width: 500px; width: 90%; box-shadow: 0 8px 32px 0 rgba(99,102,241,0.13); animation: fadeIn 0.5s;">
        <h2 style="margin-top: 0; margin-bottom: 24px; color: #6366f1; font-weight: 800; font-size: 1.5rem;">Add New User</h2>
        <div id="userModalMsg" style="display:none; margin-bottom: 16px;"></div>
        <form id="userForm" method="POST" action="/account-management/users" onsubmit="return handleUserFormSubmit(event)">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <input type="hidden" name="user_id" id="userId">

            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 7px; color: #23272f; font-weight: 700;">Name</label>
                <input type="text" name="name" id="userName" required style="width: 100%; padding: 13px; border: 1.5px solid #c7d2fe; border-radius: 10px; font-size: 1.08rem;">
            </div>

            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 7px; color: #23272f; font-weight: 700;">Email</label>
                <input type="email" name="email" id="userEmail" required style="width: 100%; padding: 13px; border: 1.5px solid #c7d2fe; border-radius: 10px; font-size: 1.08rem;">
            </div>

            <div style="margin-bottom: 22px;">
                <label style="display: block; margin-bottom: 7px; color: #23272f; font-weight: 700;">Password <span style="color: #e11d48; font-size: 0.95em;">(min. 8 characters)</span></label>
                <input type="password" name="password" id="userPassword" minlength="8" style="width: 100%; padding: 13px; border: 1.5px solid #c7d2fe; border-radius: 10px; font-size: 1.08rem;">
                <small style="color: #e11d48;">* Password must be at least 8 characters.<br></small>
                <small style="color: #6b7280;">Leave blank to keep current password (when editing)</small>
            </div>

            <div style="margin-bottom: 28px;">
                <label style="display: block; margin-bottom: 7px; color: #23272f; font-weight: 700;">Role</label>
                <select name="role" id="userRole" required style="width: 100%; padding: 13px; border: 1.5px solid #c7d2fe; border-radius: 10px; font-size: 1.08rem; background: #f9fafb;">
                    <option value="staff">Staff</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div style="display: flex; gap: 14px; justify-content: flex-end;">
                <button type="button" onclick="closeUserModal()" style="background: #6c757d; color: white; border: none; padding: 12px 24px; border-radius: 10px; font-size: 1.08rem; font-weight: 600;">Cancel</button>
                <button type="submit" style="background: linear-gradient(90deg, #6366f1 0%, #60a5fa 100%); color: white; border: none; padding: 12px 24px; border-radius: 10px; font-size: 1.08rem; font-weight: 700;">Save User</button>
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
        document.getElementById('userModalMsg').style.display = 'none';
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
                document.getElementById('userModalMsg').style.display = 'none';
            })
            .catch(error => {
                console.error('Error fetching user data:', error);
                showUserModalMsg('Error loading user data', true);
            });
    }

    function handleUserFormSubmit(event) {
        // Show loading feedback
        showUserModalMsg('Saving user...', false, true);
        // Let the form submit as normal
        return true;
    }

    function showUserModalMsg(msg, isError = false, isLoading = false) {
        const el = document.getElementById('userModalMsg');
        el.style.display = 'block';
        el.style.color = isError ? '#b91c1c' : '#2563eb';
        el.style.background = isError ? '#fee2e2' : '#dbeafe';
        el.style.borderRadius = '8px';
        el.style.padding = '8px 12px';
        el.style.fontWeight = '500';
        el.innerHTML = isLoading ? '<span class="spinner-border spinner-border-sm"></span> ' + msg : msg;
    }
</script>

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