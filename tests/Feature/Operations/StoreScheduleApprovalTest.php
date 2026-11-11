<?php

namespace Tests\Feature\Operations;

use App\Models\Store;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class StoreScheduleApprovalTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_store_schedule_approval_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('operations.store-schedules.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/StoreScheduleApproval')
                ->has('schedules.data')
                ->has('stats')
                ->has('statusOptions')
                ->has('filters')
                ->has('urls'));
    }

    public function test_store_schedule_detail_returns_matrix_and_audit(): void
    {
        $admin = $this->admin();
        $schedule = $this->makeSchedule($admin, 'submitted');

        $this->actingAs($admin)
            ->get(route('operations.store-schedules.show', $schedule))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/StoreScheduleApprovalDetail')
                ->where('schedule.id', $schedule)
                ->where('can_approve', true)
                ->has('employees', 1)
                ->has('shifts', 1)
                ->has('audit')
                ->has('summary'));
    }

    public function test_submitted_schedule_can_be_approved_with_notes(): void
    {
        $admin = $this->admin();
        $schedule = $this->makeSchedule($admin, 'submitted');

        $this->actingAs($admin)
            ->patch(route('operations.store-schedules.approve', $schedule), [
                'notes' => 'Jadwal sudah sesuai kebutuhan toko.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $row = DB::table('store_shift_schedules')->where('id', $schedule)->first();

        $this->assertSame('approved', $row->status);
        $this->assertSame((string) $admin->id, (string) $row->approved_by);
        $this->assertNotNull($row->approved_at);

        $audit = json_decode((string) $row->approval_notes, true);
        $last = end($audit);

        $this->assertSame('approve', $last['action']);
        $this->assertSame('Jadwal sudah sesuai kebutuhan toko.', $last['reason']);
    }

    public function test_submitted_schedule_can_be_returned_to_draft_with_reason(): void
    {
        $admin = $this->admin();
        $schedule = $this->makeSchedule($admin, 'submitted');

        $this->actingAs($admin)
            ->patch(route('operations.store-schedules.reject', $schedule), [
                'reason' => 'Perbaiki shift tanggal 2 karena toko libur.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $row = DB::table('store_shift_schedules')->where('id', $schedule)->first();

        $this->assertSame('draft', $row->status);
        $this->assertNull($row->submitted_at);
        $this->assertNull($row->approved_at);
        $this->assertNull($row->approved_by);

        $audit = json_decode((string) $row->approval_notes, true);
        $last = end($audit);

        $this->assertSame('reject', $last['action']);
        $this->assertSame('Perbaiki shift tanggal 2 karena toko libur.', $last['reason']);
    }

    public function test_reject_requires_reason(): void
    {
        $admin = $this->admin();
        $schedule = $this->makeSchedule($admin, 'submitted');

        $this->actingAs($admin)
            ->patch(route('operations.store-schedules.reject', $schedule), [])
            ->assertSessionHasErrors('reason');

        $row = DB::table('store_shift_schedules')->where('id', $schedule)->first();

        $this->assertSame('submitted', $row->status);
    }

    public function test_only_submitted_schedule_can_be_approved(): void
    {
        $admin = $this->admin();
        $schedule = $this->makeSchedule($admin, 'draft');

        $this->actingAs($admin)
            ->patch(route('operations.store-schedules.approve', $schedule))
            ->assertSessionHas('error');

        $row = DB::table('store_shift_schedules')->where('id', $schedule)->first();

        $this->assertSame('draft', $row->status);
        $this->assertNull($row->approved_at);
    }

    public function test_operations_navigation_exposes_store_schedule_approval(): void
    {
        $items = collect(Navigation::forUser($this->admin())['areas'][0]['groups'])
            ->flatMap(fn (array $group) => $group['items']);

        $this->assertTrue(
            $items->contains(fn (array $item): bool => $item['route'] === 'operations.store-schedules.index')
        );
    }

    public function test_user_without_schedule_permission_cannot_view_queue(): void
    {
        $user = User::create([
            'name' => 'User Tanpa Izin Jadwal',
            'username' => Str::lower(Str::random(10)),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'backoffice_access' => true,
        ]);

        $this->actingAs($user)
            ->get(route('operations.store-schedules.index'))
            ->assertForbidden();
    }

    private function makeSchedule(User $admin, string $status): string
    {
        $store = Store::create(['name' => 'Toko Uji Jadwal '.Str::upper(Str::random(6))]);
        $scheduleId = (string) Str::uuid();
        $shiftId = (string) Str::uuid();
        $submitted = $status === 'submitted';

        DB::table('store_attendance_shifts')->insert([
            'id' => $shiftId,
            'store_id' => $store->id,
            'name' => 'Shift Pagi',
            'code' => 'PAGI',
            'start_time' => '07:00:00',
            'end_time' => '15:00:00',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_shift_schedules')->insert([
            'id' => $scheduleId,
            'store_id' => $store->id,
            'schedule_month' => '2026-12-01',
            'status' => $status,
            'submitted_at' => $submitted ? now()->toDateString() : null,
            'created_by' => $admin->id,
            'approval_notes' => json_encode([
                [
                    'at' => now()->subDay()->toIso8601String(),
                    'by' => (string) $admin->id,
                    'by_name' => $admin->name,
                    'action' => 'submit',
                    'target' => 'Pengajuan jadwal Desember 2026',
                    'old' => 'Draft',
                    'new' => 'Menunggu approval',
                    'reason' => 'Diajukan ke backoffice.',
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            ['date' => '2026-12-01', 'shift_id' => $shiftId, 'day_off' => false],
            ['date' => '2026-12-02', 'shift_id' => null, 'day_off' => true],
        ] as $detail) {
            DB::table('store_shift_schedule_details')->insert([
                'id' => (string) Str::uuid(),
                'store_shift_schedule_id' => $scheduleId,
                'user_id' => $admin->id,
                'schedule_date' => $detail['date'],
                'attendance_shift_id' => $detail['shift_id'],
                'is_day_off' => $detail['day_off'],
                'is_national_holiday' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $scheduleId;
    }
}
