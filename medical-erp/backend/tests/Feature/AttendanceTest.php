<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;
    private Employee $employee;
    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();

        $adminEmployee = Employee::factory()->create(['department_id' => $this->department->id]);
        $this->admin = User::factory()->admin()->create(['employee_id' => $adminEmployee->id]);

        $this->employee = Employee::factory()->create(['department_id' => $this->department->id]);
        $this->regularUser = User::factory()->create(['employee_id' => $this->employee->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function assignMorningShift(Employee $employee): Shift
    {
        $shift = Shift::factory()->create([
            'start_time' => '08:00',
            'end_time' => '16:00',
            'grace_period_minutes' => 15,
        ]);

        ShiftAssignment::factory()->create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'effective_from' => now()->subMonth()->toDateString(),
        ]);

        return $shift;
    }

    public function test_employee_can_check_in(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson('/api/attendance/check-in');

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.source', 'self');

        $this->assertDatabaseHas('attendance_records', [
            'employee_id' => $this->employee->id,
            'attendance_date' => now()->toDateString() . ' 00:00:00',
        ]);
    }

    public function test_check_in_within_grace_period_is_present(): void
    {
        $this->assignMorningShift($this->employee);
        Carbon::setTestNow(Carbon::parse(now()->toDateString() . ' 08:10:00'));

        $response = $this->actingAs($this->regularUser)->postJson('/api/attendance/check-in');

        $response->assertCreated()
            ->assertJsonPath('data.status', 'present')
            ->assertJsonPath('data.late_minutes', 0);
    }

    public function test_check_in_after_grace_period_is_late(): void
    {
        $this->assignMorningShift($this->employee);
        Carbon::setTestNow(Carbon::parse(now()->toDateString() . ' 08:40:00'));

        $response = $this->actingAs($this->regularUser)->postJson('/api/attendance/check-in');

        $response->assertCreated()
            ->assertJsonPath('data.status', 'late')
            ->assertJsonPath('data.late_minutes', 40);
    }

    public function test_employee_cannot_check_in_twice(): void
    {
        $this->actingAs($this->regularUser)->postJson('/api/attendance/check-in')->assertCreated();

        $response = $this->actingAs($this->regularUser)->postJson('/api/attendance/check-in');

        $response->assertUnprocessable();
    }

    public function test_employee_can_check_out_with_overtime(): void
    {
        $this->assignMorningShift($this->employee);

        Carbon::setTestNow(Carbon::parse(now()->toDateString() . ' 08:00:00'));
        $this->actingAs($this->regularUser)->postJson('/api/attendance/check-in')->assertCreated();

        Carbon::setTestNow(Carbon::parse(now()->toDateString() . ' 17:00:00'));
        $response = $this->actingAs($this->regularUser)->postJson('/api/attendance/check-out');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.worked_minutes', 540)
            ->assertJsonPath('data.overtime_minutes', 60)
            ->assertJsonPath('data.early_leave_minutes', 0);
    }

    public function test_early_check_out_records_early_leave_minutes(): void
    {
        $this->assignMorningShift($this->employee);

        Carbon::setTestNow(Carbon::parse(now()->toDateString() . ' 08:00:00'));
        $this->actingAs($this->regularUser)->postJson('/api/attendance/check-in')->assertCreated();

        Carbon::setTestNow(Carbon::parse(now()->toDateString() . ' 15:00:00'));
        $response = $this->actingAs($this->regularUser)->postJson('/api/attendance/check-out');

        $response->assertOk()
            ->assertJsonPath('data.early_leave_minutes', 60)
            ->assertJsonPath('data.overtime_minutes', 0);
    }

    public function test_check_out_without_check_in_fails(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson('/api/attendance/check-out');

        $response->assertUnprocessable();
    }

    public function test_employee_can_view_own_attendance(): void
    {
        AttendanceRecord::factory()->count(3)->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->actingAs($this->regularUser)->getJson('/api/attendance/my');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['records', 'summary']]);
    }

    public function test_admin_can_list_attendance_records(): void
    {
        AttendanceRecord::factory()->count(3)->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/attendance');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_create_manual_attendance_record(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/attendance', [
            'employee_id' => $this->employee->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'absent',
            'notes' => 'غياب بدون عذر',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.source', 'manual');
    }

    public function test_cannot_create_duplicate_attendance_record(): void
    {
        AttendanceRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'attendance_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)->postJson('/api/attendance', [
            'employee_id' => $this->employee->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $response->assertUnprocessable();
    }

    public function test_admin_can_update_attendance_record(): void
    {
        $record = AttendanceRecord::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->actingAs($this->admin)->putJson("/api/attendance/{$record->id}", [
            'status' => 'mission',
            'notes' => 'مهمة عمل خارجية',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'mission');
    }

    public function test_admin_can_view_daily_summary(): void
    {
        AttendanceRecord::factory()->create([
            'employee_id' => $this->employee->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/attendance/daily-summary?date=' . now()->toDateString());

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.present', 1);
    }

    public function test_admin_can_view_monthly_report(): void
    {
        AttendanceRecord::factory()->count(3)->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->actingAs($this->admin)->getJson(sprintf(
            '/api/attendance/monthly-report?year=%d&month=%d',
            now()->year,
            now()->month
        ));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['year', 'month', 'report']]);
    }

    public function test_regular_user_cannot_access_hr_attendance_list(): void
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/attendance');

        $response->assertForbidden();
    }

    public function test_regular_user_cannot_create_manual_records(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson('/api/attendance', [
            'employee_id' => $this->employee->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $response->assertForbidden();
    }
}
