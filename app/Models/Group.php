<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // A group has many members.
    public function members()
    {
        return $this->hasMany(Member::class);
    }

    // A group has many expenses.
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
