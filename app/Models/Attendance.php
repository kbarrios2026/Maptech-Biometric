<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'status',
        'source',
        'notes',
        'overtime_reason',
        'overtime_approval_status',
        'overtime_approval_reason',
        'overtime_approved_by',
        'overtime_approved_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'overtime_approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function overtimeApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overtime_approved_by');
    }

    public static function isOvertimeCheckout(?string $checkOutTime): bool
    {
        if (empty($checkOutTime)) {
            return false;
        }

        try {
            $time = Carbon::parse($checkOutTime)->format('H:i:s');
        } catch (\Throwable) {
            return false;
        }

        return $time >= '17:01:00' && $time <= '23:59:59';
    }
}
