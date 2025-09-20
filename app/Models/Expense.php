<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'payer_id',
        'description',
        'amount_cents',
        'spent_at',
    ];

    protected $casts = [
        'spent_at' => 'date',
        'amount_cents' => 'integer',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function payer()
    {
        return $this->belongsTo(Member::class, 'payer_id');
    }
}
