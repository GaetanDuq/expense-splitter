<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'group_id'];

    // Each member belongs to one group.
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    // Expenses this member paid.
    public function paidExpenses()
    {
        return $this->hasMany(Expense::class, 'payer_id');
    }
}
