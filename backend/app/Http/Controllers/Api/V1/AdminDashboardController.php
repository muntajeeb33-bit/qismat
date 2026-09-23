<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    use RespondsWithJson;

    public function __invoke()
    {
        return $this->success([
            'registered_users' => User::where('role', 'member')->count(),
            'active_profiles' => Profile::where('moderation_status', 'approved')->count(),
            'pending_verification' => Profile::where('moderation_status', 'pending')->count(),
            'open_reports' => DB::table('reports')->where('status', 'open')->count(),
        ]);
    }
}
