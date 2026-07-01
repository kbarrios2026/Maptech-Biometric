<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDevice extends Model
{
    protected $fillable = ['employee_id', 'device_identifier', 'device_name', 'is_primary'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
