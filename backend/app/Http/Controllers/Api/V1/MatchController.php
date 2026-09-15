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
        $me = $r->user();
        $q = Profile::query()->where('user_id', '!=', $me->id)->where('visibility', '!=', 'hidden')->whereHas('user', fn ($u) => $u->where('status', 'active'));
        if ($gender = $r->string('gender')->toString()) {
            $q->where('gender', $gender);
        }
        if ($city = $r->string('city')->toString()) {
            $q->where('city', $city);
        }
        if ($religion = $r->string('religion')->toString()) {
            $q->where('religion', $religion);
        }
        if ($min = $r->integer('min_age')) {
            $q->whereDate('date_of_birth', '<=', now()->subYears($min)->toDateString());
        }
        if ($max = $r->integer('max_age')) {
            $q->whereDate('date_of_birth', '>=', now()->subYears($max + 1)->addDay()->toDateString());
        }

        $matches = $q->orderByDesc('verification_status')
            ->orderByDesc('last_active_at')
            ->paginate(30);

        return $this->success($matches);
    }
}
