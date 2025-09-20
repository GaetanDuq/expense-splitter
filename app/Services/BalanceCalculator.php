<?php

namespace App\Services;

use App\Models\Group;

class BalanceCalculator
{
    /**
     * Calculate equal-split balances for a group.
     *
     * Definitions:
     * - total_cents: sum of all expenses in minor units (cents)
     * - share_cents: each member's fair share (equal split)
     * - paid_cents: how much a member actually paid
     * - balance_cents: paid_cents - share_cents
     *     > 0  = should RECEIVE
     *     < 0  = should PAY
     *
     * We distribute any remainder cents fairly:
     * intdiv(total, n) for everyone, then +1 cent to the first (total % n) members.
     * This guarantees the shares add up exactly to the total (no lost cents).
     */
    public function calculate(Group $group): array
    {
        $group->load(['members:id,group_id,name', 'expenses:id,group_id,payer_id,amount_cents']);

        $members = $group->members->values(); // ensure 0..n-1 indexing
        $n = $members->count();

        // Edge cases: 0 or 1 member -> trivially zero balances
        if ($n <= 1) {
            return [
                'group' => ['id' => $group->id, 'name' => $group->name],
                'summary' => [
                    'total_cents' => 0,
                    'total'       => '0.00',
                    'members'     => $n,
                    'per_head_cents' => 0,
                    'per_head'    => '0.00',
                ],
                'per_member' => $members->map(fn ($m) => [
                    'member_id'      => $m->id,
                    'name'           => $m->name,
                    'paid_cents'     => 0,
                    'paid'           => '0.00',
                    'share_cents'    => 0,
                    'share'          => '0.00',
                    'balance_cents'  => 0,
                    'balance'        => '0.00',
                ])->all(),
                'settlements' => [],
            ];
        }

        $total = (int) $group->expenses->sum('amount_cents'); // integer
        $baseShare = intdiv($total, $n);
        $remainder = $total % $n;

        // Map member_id -> paid_cents (sum of expenses they paid)
        $paidByMember = [];
        foreach ($members as $m) {
            $paidByMember[$m->id] = 0;
        }
        foreach ($group->expenses as $e) {
            $paidByMember[$e->payer_id] += (int) $e->amount_cents;
        }

        // Build per-member rows with fair remainder distribution
        $perMember = [];
        $sumBalances = 0;
        foreach ($members as $index => $m) {
            $paid = $paidByMember[$m->id] ?? 0;

            // Distribute remainder: first $remainder members get +1 cent
            $share = $baseShare + ($index < $remainder ? 1 : 0);

            $balance = $paid - $share;
            $sumBalances += $balance;

            $perMember[] = [
                'member_id'      => $m->id,
                'name'           => $m->name,
                'paid_cents'     => $paid,
                'paid'           => $this->fmt($paid),
                'share_cents'    => $share,
                'share'          => $this->fmt($share),
                'balance_cents'  => $balance,
                'balance'        => $this->fmt($balance),
            ];
        }

        // Safety: balances should sum to 0; if off due to any future edits, fix last entry.
        if ($sumBalances !== 0 && count($perMember) > 0) {
            $last = count($perMember) - 1;
            $perMember[$last]['balance_cents'] -= $sumBalances;
            $perMember[$last]['balance'] = $this->fmt($perMember[$last]['balance_cents']);
        }

        // Optional: generate minimal settlement transfers (greedy)
        $settlements = $this->settlements($perMember);

        return [
            'group' => ['id' => $group->id, 'name' => $group->name],
            'summary' => [
                'total_cents'    => $total,
                'total'          => $this->fmt($total),
                'members'        => $n,
                'per_head_cents' => $baseShare, // base (some may +1)
                'per_head'       => $this->fmt($baseShare),
            ],
            'per_member'  => $perMember,
            'settlements' => $settlements,
        ];
    }

    /**
     * Produce a simple transfer plan between debtors (<0) and creditors (>0).
     * Output rows like: { from, to, amount_cents, amount }
     */
    private function settlements(array $perMember): array
    {
        $creditors = []; // [ [name, cents] ... ] (balance > 0)
        $debtors   = []; // [ [name, cents] ... ] (balance < 0)

        foreach ($perMember as $row) {
            $bal = (int) $row['balance_cents'];
            if ($bal > 0)  $creditors[] = ['name' => $row['name'], 'cents' => $bal];
            if ($bal < 0)  $debtors[]   = ['name' => $row['name'], 'cents' => -$bal]; // store as positive needed
        }

        // Greedy match
        $i = 0; $j = 0;
        $out = [];
        while ($i < count($debtors) && $j < count($creditors)) {
            $pay = min($debtors[$i]['cents'], $creditors[$j]['cents']);
            $out[] = [
                'from'         => $debtors[$i]['name'],
                'to'           => $creditors[$j]['name'],
                'amount_cents' => $pay,
                'amount'       => $this->fmt($pay),
            ];
            $debtors[$i]['cents']   -= $pay;
            $creditors[$j]['cents'] -= $pay;
            if ($debtors[$i]['cents'] === 0)   $i++;
            if ($creditors[$j]['cents'] === 0) $j++;
        }

        return $out;
    }

    /** Convert cents -> "major units" string with 2 decimals (e.g., 7420 -> "74.20"). */
    private function fmt(int $cents): string
    {
        // Allows negative values too (e.g., -123 -> "-1.23")
        $sign = $cents < 0 ? '-' : '';
        $c = abs($cents);
        return $sign . number_format($c / 100, 2, '.', '');
    }
}
