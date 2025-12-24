<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = ['name', 'slug', 'status'];

    public function sales()
    {
        return $this->hasMany(Sale::class, 'table_number', 'name');
    }
}
