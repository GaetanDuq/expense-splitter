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
        $expenses = $group->expenses()
            ->with('payer:id,name')
            ->orderByDesc('spent_at')
            ->orderByDesc('id')
            ->get(['id','group_id','payer_id','description','amount_cents','spent_at','created_at']);

        return response()->json($expenses);
    }

    public function store(Request $request, Group $group)
    {
        $data = $request->validate([
            'payer_id'    => ['required','integer','exists:members,id'],
            'description' => ['required','string','max:140'],
            'amount'      => ['required','numeric','min:0.01'],
            'spent_at'    => ['nullable','date'],
        ]);

        $belongs = $group->members()->whereKey($data['payer_id'])->exists();
        if (!$belongs) {
            return response()->json(['message' => 'Payer must be a member of the group.'], 422);
        }

        $expense = $group->expenses()->create([
            'payer_id'     => $data['payer_id'],
            'description'  => $data['description'],
            'amount_cents' => (int) round($data['amount'] * 100),
            'spent_at'     => $data['spent_at'] ?? null,
        ]);

        $expense->load('payer:id,name');

        return response()->json($expense, 201);
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return response()->noContent();
    }
}
