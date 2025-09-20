<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Services\BalanceCalculator;

class GroupController extends Controller
{
    // list all groups
    public function index()
    {
        return Group::select('id','name','created_at')
            ->withCount(['members','expenses'])
            ->orderByDesc('id')
            ->get();
    }

    // make a new group (just name)
    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required','string','max:100']]);
        return response()->json(Group::create($data), 201);
    }

    // show one group with members and last expenses
    public function show(Group $group)
    {
        $group->load([
            'members:id,group_id,name,created_at',
            'expenses' => fn($q) => $q->latest('spent_at')->latest()->limit(50),
            'expenses.payer:id,name',
        ]);
        return $group;
    }

    // delete a group (members and expenses go away too because of cascade)
    public function destroy(Group $group)
    {
        $group->delete();
        return response()->noContent();
    }

    // compute balances for this group
    public function balances(Group $group, BalanceCalculator $calc)
    {
        return $calc->calculate($group);
    }
}
