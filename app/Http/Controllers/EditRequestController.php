<?php

namespace App\Http\Controllers;

use App\Models\EditRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EditRequestController extends Controller
{
    // Staff submits an edit request
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'request_details' => 'nullable|string',
        ]);

        EditRequest::create([
            'product_id' => $request->product_id ?? null,
            'user_id' => Auth::id(),
            'request_details' => $request->request_details,
            'status' => 'pending',
            'completed' => false,
        ]);

        return response()->json(['message' => 'Edit request submitted successfully.'], 201);
    }

    // Admin views all requests
    public function index()
    {
        // Show only the latest edit request per staff user
        // Show all edit requests (not grouped)
        $allRequests = \App\Models\EditRequest::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        $pendingEditRequests = \App\Models\EditRequest::with('user')->where('status', 'pending')->orderBy('created_at', 'desc')->get();
        $pendingApprovalCount = $pendingEditRequests->count();
        $notificationCount = $pendingApprovalCount; // No reorder notifications on approval page
        return view('pages.new_approval_requests', [
            'editRequests' => $allRequests,
            'pendingEditRequests' => $pendingEditRequests,
            'pendingApprovalCount' => $pendingApprovalCount,
            'notificationCount' => $notificationCount
        ]);
    }

    // Admin approves a request
    public function approve($id)
    {
        $request = EditRequest::findOrFail($id);
        $request->status = 'approved';
        $request->save();
        return redirect()->back()->with('success', 'Request approved.');
    }

    // Admin rejects a request
    public function reject($id)
    {
        $request = EditRequest::findOrFail($id);
        $request->status = 'rejected';
        $request->save();
        return redirect()->back()->with('success', 'Request rejected.');
    }
}
