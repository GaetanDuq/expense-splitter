<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Services\BalanceCalculator;

class GroupController extends Controller
{
    // GET /api/groups
    public function index()
    {
        $groups = Group::query()
            ->select('id', 'name', 'created_at')
            ->withCount(['members', 'expenses'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($groups);
    }

    // POST /api/groups  { name }
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $group = Group::create($data);

        return response()->json($group, 201);
    }

    // GET /api/groups/{group}
    public function show(Group $group)
    {
        $group->load([
            'members:id,group_id,name,created_at',
            'expenses' => fn ($q) => $q->latest('spent_at')->latest()->limit(50),
            'expenses.payer:id,name',
        ]);

        return response()->json($group);
    }

    // DELETE /api/groups/{group}
    public function destroy(Group $group)
    {
        $group->delete();
        return response()->noContent();
    }

    // GET /api/groups/{group}/balances
    public function balances(Group $group, BalanceCalculator $calc)
    {
        $result = $calc->calculate($group);
        return response()->json($result);
    }
}
