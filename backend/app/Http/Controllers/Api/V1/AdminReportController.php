<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request)
    {
        $data = $request->validate([
            'status' => ['sometimes', 'in:open,resolved,dismissed'],
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
        ]);

        $reports = Report::query()
            ->with([
                'reporter:id,name,email',
                'reporter.profile:id,user_id,profile_code,display_name',
                'reportedUser:id,name,email,status',
                'reportedUser.profile:id,user_id,profile_code,display_name,moderation_status',
            ])
            ->where('status', $data['status'] ?? 'open')
            ->oldest()
            ->paginate($data['per_page'] ?? 20);

        return $this->success($reports);
    }

    public function resolve(Request $request, Report $report)
    {
        $data = $request->validate([
            'action' => ['required', 'in:resolved,dismissed,suspended'],
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        return DB::transaction(function () use ($request, $report, $data) {
            $report = Report::query()->lockForUpdate()->findOrFail($report->id);
            abort_unless($report->status === 'open', 409, 'Only open reports can be resolved.');

            $before = $report->only(['status', 'resolution_action', 'resolution_notes', 'resolved_by', 'resolved_at']);
            if ($data['action'] === 'suspended') {
                $report->reportedUser()->update(['status' => 'suspended']);
                $report->reportedUser?->profile?->update(['moderation_status' => 'suspended', 'discovery_opt_in' => false]);
                $report->reportedUser?->tokens()->delete();
            }

            $report->update([
                'status' => $data['action'] === 'dismissed' ? 'dismissed' : 'resolved',
                'resolution_action' => $data['action'],
                'resolution_notes' => $data['notes'],
                'resolved_by' => $request->user()->id,
                'resolved_at' => now(),
            ]);

            AdminAuditLog::create([
                'admin_id' => $request->user()->id,
                'action' => 'report.'.$data['action'],
                'target_type' => 'report',
                'target_id' => $report->id,
                'old_values' => $before,
                'new_values' => $report->only(['status', 'resolution_action', 'resolution_notes', 'resolved_by', 'resolved_at']),
                'ip_address' => $request->ip(),
            ]);

            return $this->success($report->fresh(['reportedUser.profile']), 'Report resolved.');
        });
    }
}
