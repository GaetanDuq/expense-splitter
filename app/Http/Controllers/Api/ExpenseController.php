<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Expense;

class ExpenseController extends Controller
{
    public function index(Group $group)
    {
        return $group->expenses()
            ->with('payer:id,name')
            ->orderByDesc('spent_at')
            ->orderByDesc('id')
            ->get(['id','group_id','payer_id','description','amount_cents','spent_at','created_at']);
    }

    public function store(Request $request, Group $group)
    {
        // I send amount in yen (like 500). I store cents (like 50000).
        $data = $request->validate([
            'payer_id'    => ['required','integer','exists:members,id'],
            'description' => ['required','string','max:140'],
            'amount'      => ['required','numeric','min:0.01'],
            'spent_at'    => ['nullable','date'],
        ]);

        // make sure payer belongs to this group
        $ok = $group->members()->whereKey($data['payer_id'])->exists();
        if (!$ok) return response()->json(['message' => 'Payer must be in this group'], 422);

        $saved = $group->expenses()->create([
            'payer_id'     => $data['payer_id'],
            'description'  => $data['description'],
            'amount_cents' => (int) round($data['amount'] * 100),
            'spent_at'     => $data['spent_at'] ?? null,
        ]);

        return response()->json($saved->load('payer:id,name'), 201);
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return response()->noContent();
    }
}
