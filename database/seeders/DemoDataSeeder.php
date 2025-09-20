<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;
use App\Models\Member;
use App\Models\Expense;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    /**
     * Convert a major-unit amount (e.g., 42.00) to integer minor units (cents).
     * We store money in cents to avoid floating-point bugs.
     */
    private function cents($major): int
    {
        return (int) round($major * 100);
    }

    public function run(): void
    {
        // -------- Group 1: Kyoto Trip --------
        $kyoto = Group::create(['name' => 'Kyoto Trip']);

        $kMembers = collect([
            Member::create(['group_id' => $kyoto->id, 'name' => 'Alice']),
            Member::create(['group_id' => $kyoto->id, 'name' => 'Bob']),
            Member::create(['group_id' => $kyoto->id, 'name' => 'Charlie']),
        ]);

        // A few fixed expenses so the balances are interesting
        $dates = [
            Carbon::now()->subDays(6),
            Carbon::now()->subDays(4),
            Carbon::now()->subDays(2),
            Carbon::now()->subDay(),
        ];

        Expense::create([
            'group_id'     => $kyoto->id,
            'payer_id'     => $kMembers[0]->id,   // Alice
            'description'  => 'Train tickets',
            'amount_cents' => $this->cents(120.00),
            'spent_at'     => $dates[0],
        ]);

        Expense::create([
            'group_id'     => $kyoto->id,
            'payer_id'     => $kMembers[1]->id,   // Bob
            'description'  => 'Lunch',
            'amount_cents' => $this->cents(36.50),
            'spent_at'     => $dates[1],
        ]);

        Expense::create([
            'group_id'     => $kyoto->id,
            'payer_id'     => $kMembers[2]->id,   // Charlie
            'description'  => 'Temple tickets',
            'amount_cents' => $this->cents(27.00),
            'spent_at'     => $dates[2],
        ]);

        Expense::create([
            'group_id'     => $kyoto->id,
            'payer_id'     => $kMembers[0]->id,   // Alice
            'description'  => 'Dinner',
            'amount_cents' => $this->cents(74.20),
            'spent_at'     => $dates[3],
        ]);

        // -------- Group 2: Roommates --------
        $home = Group::create(['name' => 'Roommates']);

        $hMembers = collect([
            Member::create(['group_id' => $home->id, 'name' => 'Dana']),
            Member::create(['group_id' => $home->id, 'name' => 'Eli']),
            Member::create(['group_id' => $home->id, 'name' => 'Fin']),
        ]);

        Expense::create([
            'group_id'     => $home->id,
            'payer_id'     => $hMembers[1]->id,   // Eli
            'description'  => 'Groceries',
            'amount_cents' => $this->cents(58.80),
            'spent_at'     => Carbon::now()->subDays(3),
        ]);

        Expense::create([
            'group_id'     => $home->id,
            'payer_id'     => $hMembers[0]->id,   // Dana
            'description'  => 'Cleaning supplies',
            'amount_cents' => $this->cents(22.40),
            'spent_at'     => Carbon::now()->subDays(1),
        ]);
    }
}
