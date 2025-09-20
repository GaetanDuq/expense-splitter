<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Member;

class MemberController extends Controller
{
    public function store(Request $request, Group $group)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
        ]);

        $member = $group->members()->create($data);

        return response()->json($member, 201);
    }

    public function destroy(Member $member)
    {
        $member->delete();
        return response()->noContent();
    }
}
