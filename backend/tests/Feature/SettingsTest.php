<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::factory()->create();

        $adminEmployee = Employee::factory()->create(['department_id' => $department->id]);
        $this->admin = User::factory()->admin()->create(['employee_id' => $adminEmployee->id]);

        $employee = Employee::factory()->create(['department_id' => $department->id]);
        $this->regularUser = User::factory()->create(['employee_id' => $employee->id]);
    }

    public function test_admin_can_list_settings(): void
    {
        SystemSetting::create([
            'id' => Str::uuid(),
            'key' => 'company_name',
            'value' => 'شركة اختبار',
            'group' => 'general',
            'label' => 'Company Name',
            'label_ar' => 'اسم الشركة',
            'is_public' => true,
            'is_editable' => true,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/settings');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_filter_settings_by_group(): void
    {
        SystemSetting::create([
            'id' => Str::uuid(),
            'key' => 'leave_approval_required',
            'value' => 'true',
            'group' => 'leave',
            'label' => 'Leave Approval Required',
            'label_ar' => 'اعتماد الإجازة مطلوب',
            'is_public' => false,
            'is_editable' => true,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/settings?group=leave');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_update_editable_setting(): void
    {
        $setting = SystemSetting::create([
            'id' => Str::uuid(),
            'key' => 'company_name',
            'value' => 'شركة قديمة',
            'group' => 'general',
            'label' => 'Company Name',
            'label_ar' => 'اسم الشركة',
            'is_public' => true,
            'is_editable' => true,
        ]);

        $response = $this->actingAs($this->admin)->putJson("/api/settings/{$setting->id}", [
            'value' => 'شركة جديدة',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('system_settings', [
            'id' => $setting->id,
            'value' => 'شركة جديدة',
        ]);
    }

    public function test_cannot_update_non_editable_setting(): void
    {
        $setting = SystemSetting::create([
            'id' => Str::uuid(),
            'key' => 'system_version',
            'value' => '1.0.0',
            'group' => 'system',
            'label' => 'System Version',
            'label_ar' => 'إصدار النظام',
            'is_public' => true,
            'is_editable' => false,
        ]);

        $response = $this->actingAs($this->admin)->putJson("/api/settings/{$setting->id}", [
            'value' => '2.0.0',
        ]);

        $response->assertForbidden();
    }

    public function test_regular_user_cannot_access_settings(): void
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/settings');

        $response->assertForbidden();
    }

    public function test_update_requires_value(): void
    {
        $setting = SystemSetting::create([
            'id' => Str::uuid(),
            'key' => 'test_key',
            'value' => 'old',
            'group' => 'general',
            'label' => 'Test',
            'label_ar' => 'اختبار',
            'is_public' => true,
            'is_editable' => true,
        ]);

        $response = $this->actingAs($this->admin)->putJson("/api/settings/{$setting->id}", []);

        $response->assertUnprocessable();
    }
}
