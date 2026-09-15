<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request) { return response()->json($request->user()->profile); }

    public function update(Request $request)
    {
        $data = $request->validate([
            'gender'=>['sometimes','in:male,female,other'], 'date_of_birth'=>['sometimes','date','before:-18 years'],
            'height_cm'=>['sometimes','integer','between:100,250'], 'marital_status'=>['sometimes','string','max:40'],
            'religion'=>['sometimes','nullable','string','max:80'], 'community'=>['sometimes','nullable','string','max:100'],
            'mother_tongue'=>['sometimes','nullable','string','max:80'], 'country'=>['sometimes','nullable','string','max:80'],
            'state'=>['sometimes','nullable','string','max:80'], 'city'=>['sometimes','nullable','string','max:80'],
            'education'=>['sometimes','nullable','string','max:180'], 'occupation'=>['sometimes','nullable','string','max:180'],
            'about_me'=>['sometimes','nullable','string','max:3000'], 'partner_expectations'=>['sometimes','array'],
        ]);
        $profile = $request->user()->profile()->updateOrCreate(['user_id'=>$request->user()->id], $data);
        return response()->json($profile);
    }
}
