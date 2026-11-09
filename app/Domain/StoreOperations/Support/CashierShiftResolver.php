<?php

namespace App\Domain\StoreOperations\Support;

use Illuminate\Support\Facades\DB;

class CashierShiftResolver
{
    public function attendanceShiftSubquery()
    {
        return DB::table('store_attendances as attendance')
            ->join('store_attendance_shifts as shift', 'shift.id', '=', 'attendance.attendance_shift_id')
            ->selectRaw('
                DISTINCT ON (attendance.store_id, attendance.user_id, attendance.date)
                attendance.store_id,
                attendance.user_id,
                attendance.date as shift_date,
                shift.id as shift_id,
                shift.name as shift_name,
                shift.code as shift_code,
                shift.start_time as shift_start_time
            ')
            ->whereNotNull('attendance.attendance_shift_id')
            ->orderBy('attendance.store_id')
            ->orderBy('attendance.user_id')
            ->orderBy('attendance.date')
            ->orderByDesc('attendance.clock_in_at')
            ->orderBy('shift.start_time');
    }

    public function scheduledShiftSubquery()
    {
        return DB::table('store_shift_schedule_details as detail')
            ->join('store_shift_schedules as schedule', 'schedule.id', '=', 'detail.store_shift_schedule_id')
            ->join('store_attendance_shifts as shift', 'shift.id', '=', 'detail.attendance_shift_id')
            ->selectRaw('
                DISTINCT ON (schedule.store_id, detail.user_id, detail.schedule_date)
                schedule.store_id,
                detail.user_id,
                detail.schedule_date as shift_date,
                shift.id as shift_id,
                shift.name as shift_name,
                shift.code as shift_code,
                shift.start_time as shift_start_time
            ')
            ->whereNotNull('detail.attendance_shift_id')
            ->where('detail.is_day_off', false)
            ->where('detail.is_national_holiday', false)
            ->orderBy('schedule.store_id')
            ->orderBy('detail.user_id')
            ->orderBy('detail.schedule_date')
            ->orderBy('shift.start_time');
    }
}
