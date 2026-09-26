<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;

class AdminAuditController extends Controller
{
    use RespondsWithJson;

    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'action' => ['sometimes', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        $query = AdminAuditLog::query()
            ->with('admin:id,name,email')
            ->latest('id');

        if ($action = trim((string) ($data['action'] ?? ''))) {
            $query->where('action', 'like', "%{$action}%");
        }

        return $this->success($query->paginate($data['per_page'] ?? 30));
    }
}
