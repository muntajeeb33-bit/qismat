<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    use RespondsWithJson;

    public function index(Request $r)
    {
        $filters = $r->validate([
            'gender' => ['sometimes', 'in:male,female,other'],
            'city' => ['sometimes', 'string', 'max:80'],
            'religion' => ['sometimes', 'string', 'max:80'],
            'min_age' => ['sometimes', 'integer', 'between:18,100'],
            'max_age' => ['sometimes', 'integer', 'between:18,100', 'gte:min_age'],
        ]);

        $me = $r->user();
        $q = Profile::query()->where('user_id', '!=', $me->id)->where('visibility', '!=', 'hidden')->where('moderation_status', 'approved')->where('discovery_opt_in', true)->whereNotNull('date_of_birth')->whereDate('date_of_birth', '<=', now()->subYears(18)->toDateString())->whereHas('user', fn ($u) => $u->where('status', 'active')->whereNotNull('email_verified_at'));
        if ($gender = $filters['gender'] ?? null) {
            $q->where('gender', $gender);
        }
        if ($city = $filters['city'] ?? null) {
            $q->where('city', $city);
        }
        if ($religion = $filters['religion'] ?? null) {
            $q->where('religion', $religion);
        }
        if ($min = $filters['min_age'] ?? null) {
            $q->whereDate('date_of_birth', '<=', now()->subYears($min)->toDateString());
        }
        if ($max = $filters['max_age'] ?? null) {
            $q->whereDate('date_of_birth', '>=', now()->subYears($max + 1)->addDay()->toDateString());
        }

        $matches = $q->orderByDesc('verification_status')
            ->orderByDesc('last_active_at')
            ->paginate(30);

        return $this->success($matches);
    }
}
