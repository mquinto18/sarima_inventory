<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->get();
        $reorderCount = \App\Http\Controllers\ProductController::getReorderCount();
        $reorderNotifications = \App\Http\Controllers\ProductController::getReorderNotifications();
        $pendingApprovalCount = \App\Models\EditRequest::where('status', 'pending')->count();
        $notificationCount = $pendingApprovalCount + $reorderCount;

        return view('pages.account-management', compact('users', 'reorderCount', 'reorderNotifications', 'pendingApprovalCount', 'notificationCount'));
    }

    public function store(Request $request)
    {
        // The unique index covers archived rows too, so without this the admin
        // would be told the address is taken by an account they cannot see
        // anywhere in the list.
        $archived = User::onlyTrashed()->where('email', $request->input('email'))->first();
        if ($archived) {
            return back()->withInput()->withErrors([
                'email' => "That email belongs to \"{$archived->name}\", an archived account. Restore it from Settings instead of creating a new one.",
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,manager,staff',
        ], [
            'email.unique' => 'That email address is already registered to another account.',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect('/account-management')->with('success', 'User created successfully');
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:admin,manager,staff',
        ], [
            'email.unique' => 'That email address is already registered to another account.',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect('/account-management')->with('success', 'User updated successfully');
    }

    /**
     * Archive (soft delete) an account. Accounts are never hard deleted: they
     * are referenced by edit_requests, stock_movements and purchase_orders, and
     * removing the row would orphan that history. Archived accounts cannot log
     * in and are restorable from Settings -> Archived Users.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent archiving yourself
        if ($user->id === auth()->id()) {
            return redirect('/account-management')->with('error', 'You cannot archive your own account');
        }

        // Never archive the last active admin — doing so locks everyone out of
        // Account Management, Approval Requests and archived-user recovery,
        // with no way back in through the UI.
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return redirect('/account-management')
                ->with('error', 'You cannot archive the only administrator account. Promote another user to admin first.');
        }

        $user->delete();

        return redirect('/account-management')
            ->with('success', "\"{$user->name}\" has been archived. You can restore the account from Settings.");
    }

    /**
     * Restore an archived account from Settings -> Archived Users.
     */
    public function restore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);

        // The email unique index still covers archived rows, but a restore can
        // only collide if someone freed the address by archiving this account
        // and then reusing it — which the store/update validation prevents.
        if (User::where('email', $user->email)->where('id', '!=', $user->id)->exists()) {
            return redirect('/settings')
                ->with('error', "Cannot restore \"{$user->name}\": {$user->email} now belongs to another account.");
        }

        $user->restore();

        return redirect('/settings')->with('success', "\"{$user->name}\" has been restored and can sign in again.");
    }
}
