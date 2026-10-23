<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuditWebController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $action = $request->query('action');

        $query = AuditLog::where('organization_id', $user->organization_id)
            ->with('user')
            ->latest();

        if ($action) {
            $query->where('action', 'like', "%{$action}%");
        }

        $logs = $query->paginate(30);

        return view('audit.index', compact('logs', 'action'));
    }
}
