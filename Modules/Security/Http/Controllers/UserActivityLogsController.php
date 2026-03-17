<?php

namespace Modules\Security\Http\Controllers;

use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Illuminate\Foundation\Validation\ValidatesRequests;

class UserActivityLogsController extends Controller
{
    use ValidatesRequests;

    public function index()
    {
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Security::UserActivityLogs', [
            'users' => $users
        ]);
    }

    public function getData(Request $request)
    {
        $query = UserActivityLog::with('user:id,name,email')
            ->when($request->user_id, function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            })
            ->when($request->date_start, function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->date_start);
            })
            ->when($request->date_end, function ($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->date_end);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($query);
    }
}
