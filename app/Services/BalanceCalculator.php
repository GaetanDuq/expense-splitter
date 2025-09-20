<?php

namespace App\Services;

use App\Models\Group;

class BalanceCalculator
{
    // Main function: returns totals, per member balances, and a small settlement plan.
    public function calculate(Group $group): array
    {
        $group->load(['members:id,group_id,name', 'expenses:id,group_id,payer_id,amount_cents']);

        $members = $group->members->values();
        $count = $members->count();

        if ($count <= 1) {
            return [
                'group'   => ['id' => $group->id, 'name' => $group->name],
                'summary' => ['total_cents' => 0, 'total' => '0.00', 'members' => $count, 'per_head_cents' => 0, 'per_head' => '0.00'],
                'per_member' => $members->map(fn($m) => [
                    'member_id'=>$m->id,'name'=>$m->name,'paid_cents'=>0,'paid'=>'0.00','share_cents'=>0,'share'=>'0.00','balance_cents'=>0,'balance'=>'0.00'
                ])->all(),
                'settlements' => [],
            ];
        }

        // add all expenses (in cents, no floats)
        $total = (int) $group->expenses->sum('amount_cents');

        // equal split using integers (fair split)
        $base = intdiv($total, $count);
        $rem  = $total % $count;

        // how much each person paid
        $paid = [];
        foreach ($members as $m) $paid[$m->id] = 0;
        foreach ($group->expenses as $e) $paid[$e->payer_id] += (int) $e->amount_cents;

        // build per member rows
        $rows = [];
        $sum = 0;
        foreach ($members as $i => $m) {
            $p = $paid[$m->id] ?? 0;
            $share = $base + ($i < $rem ? 1 : 0); // first "rem" people get +1 cent
            $bal = $p - $share;                   // >0 = gets, <0 = owes
            $sum += $bal;

            $rows[] = [
                'member_id'     => $m->id,
                'name'          => $m->name,
                'paid_cents'    => $p,
                'paid'          => $this->fmt($p),
                'share_cents'   => $share,
                'share'         => $this->fmt($share),
                'balance_cents' => $bal,
                'balance'       => $this->fmt($bal),
            ];
        }

        // small safety: balances should sum to 0
        if ($sum !== 0 && count($rows)) {
            $last = count($rows) - 1;
            $rows[$last]['balance_cents'] -= $sum;
            $rows[$last]['balance'] = $this->fmt($rows[$last]['balance_cents']);
        }

        return [
            'group'   => ['id' => $group->id, 'name' => $group->name],
            'summary' => [
                'total_cents'    => $total,
                'total'          => $this->fmt($total),
                'members'        => $count,
                'per_head_cents' => $base, // note: some people get +1 cent
                'per_head'       => $this->fmt($base),
            ],
            'per_member'  => $rows,
            'settlements' => $this->settle($rows),
        ];
    }

    // make a simple list of payments from debtors to creditors
    private function settle(array $rows): array
    {
        $plus = []; // people who should receive
        $minus = []; // people who should pay

        foreach ($rows as $r) {
            $b = (int) $r['balance_cents'];
            if ($b > 0)  $plus[]  = ['name' => $r['name'], 'c' => $b];
            if ($b < 0)  $minus[] = ['name' => $r['name'], 'c' => -$b]; // store positive
        }

        $i = 0; $j = 0; $out = [];
        while ($i < count($minus) && $j < count($plus)) {
            $pay = min($minus[$i]['c'], $plus[$j]['c']);
            $out[] = ['from' => $minus[$i]['name'], 'to' => $plus[$j]['name'], 'amount_cents' => $pay, 'amount' => $this->fmt($pay)];
            $minus[$i]['c'] -= $pay;
            $plus[$j]['c']  -= $pay;
            if ($minus[$i]['c'] === 0) $i++;
            if ($plus[$j]['c']  === 0) $j++;
        }
        return $out;
    }

    // convert cents to "xx.yy" string (works with negatives)
    private function fmt(int $c): string
    {
        $sign = $c < 0 ? '-' : '';
        $v = abs($c);
        return $sign . number_format($v / 100, 2, '.', '');
    }
}
