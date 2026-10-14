<?php

namespace Tests\Feature;

use App\Models\AttendanceTransaction;
use App\Models\Course;
use App\Models\User;
use App\Notifications\LowAllowanceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Lab 6 – Part 10 Testing Checklist
 * stock-in  = "restore"  (absences_remaining goes UP)
 * stock-out = "absence"  (absences_remaining goes DOWN)
 * reorder level = warning_level
 */
class Lab6ChecklistTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $student;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->forceFill(['is_admin' => true])->save();
        $this->student = User::factory()->create();

        $this->course = Course::create([
            'course_code' => 'IT301', 'course_name' => 'Web Dev', 'category' => 'Major',
            'units' => 3, 'grade' => 3.5, 'instructor' => 'Prof. Test',
            'attendance_rate' => 100, 'total_sessions' => 30,
            'absences_allowed' => 6, 'absences_remaining' => 6, 'warning_level' => 2,
        ]);
    }

    private function record(string $type, int $qty, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->student)
            ->from(route('attendance-transactions.create'))
            ->post(route('attendance-transactions.store'), [
                'course_id' => $this->course->id, 'type' => $type, 'quantity' => $qty,
                'reference_number' => 'REF-1', 'notes' => 'test',
            ]);
    }

    /** 1. Stock-in (restore) increases quantity */
    public function test_01_stock_in_increases_quantity(): void
    {
        $this->course->update(['absences_remaining' => 3]);
        $this->record('restore', 2)->assertRedirect(route('attendance-transactions.index'));

        $this->assertSame(5, $this->course->fresh()->absences_remaining);
        $this->assertDatabaseHas('attendance_transactions', ['type' => 'restore', 'quantity' => 2]);
    }

    /** 2. Stock-out (absence) decreases quantity */
    public function test_02_stock_out_decreases_quantity(): void
    {
        $this->record('absence', 1)->assertRedirect(route('attendance-transactions.index'));

        $c = $this->course->fresh();
        $this->assertSame(5, $c->absences_remaining);
        $this->assertSame(97, $c->attendance_rate); // 1 of 30 sessions missed
        $this->assertDatabaseHas('attendance_transactions', ['type' => 'absence', 'quantity' => -1]);
    }

    /** 3. Stock-out above available quantity is rejected */
    public function test_03_stock_out_exceeding_quantity_is_rejected(): void
    {
        $this->record('absence', 7)->assertSessionHasErrors('quantity');
        $this->assertSame(6, $this->course->fresh()->absences_remaining);
        $this->assertDatabaseCount('attendance_transactions', 0);

        // Model-level guard too (bypassing the form request)
        $this->expectException(\App\Exceptions\AbsenceAllowanceException::class);
        AttendanceTransaction::create([
            'course_id' => $this->course->id, 'user_id' => $this->student->id,
            'type' => 'absence', 'quantity' => -10,
        ]);
    }

    /** 4. Low-stock notification when falling to/below reorder level */
    public function test_04_low_allowance_notification_sent(): void
    {
        Notification::fake();

        $this->record('absence', 3); // 6 -> 3, still above warning level 2
        Notification::assertNothingSent();

        $this->record('absence', 1); // 3 -> 2, crosses warning level
        Notification::assertSentTo([$this->admin, $this->student], LowAllowanceNotification::class);
    }

    public function test_04b_notification_saved_to_database(): void
    {
        $this->record('absence', 4); // 6 -> 2
        $this->assertDatabaseHas('notifications', [
            'type' => LowAllowanceNotification::class, 'notifiable_id' => $this->student->id,
        ]);
        $this->assertSame(1, $this->student->fresh()->unreadNotifications()->count());
    }

    /** 5. Dashboard shows accurate statistics */
    public function test_05_dashboard_statistics_are_accurate(): void
    {
        Course::create([
            'course_code' => 'IT302', 'course_name' => 'Networks', 'category' => 'Major',
            'units' => 3, 'grade' => 2.5, 'instructor' => 'Prof. B', 'attendance_rate' => 100,
            'total_sessions' => 30, 'absences_allowed' => 6, 'absences_remaining' => 1, 'warning_level' => 2,
        ]);
        $this->record('absence', 2);

        $this->actingAs($this->student)->get('/dashboard')->assertOk();

        $this->actingAs($this->student)->getJson(route('api.dashboard.stats'))
            ->assertOk()
            ->assertJsonPath('data.total_courses', 2)
            ->assertJsonPath('data.gpa', 3)
            ->assertJsonPath('data.at_risk', 1)
            ->assertJsonPath('data.low_allowance', 1)
            ->assertJsonPath('data.absences_used', 7)
            ->assertJsonPath('data.logged_today', 1);
    }

    /** 6. AJAX polling endpoints (JS checked separately in a real browser) */
    public function test_06_polling_endpoints_return_json_and_reflect_changes(): void
    {
        $this->actingAs($this->student)->get('/dashboard')
            ->assertSee('dashboard-live.js', false)
            ->assertSee(route('api.dashboard.stats'), false);

        $before = $this->getJson(route('api.dashboard.stats'))->json('data.absences_used');
        $this->record('absence', 1);
        $after = $this->getJson(route('api.dashboard.stats'))->json('data.absences_used');
        $this->assertSame($before + 1, $after);

        $this->getJson(route('api.transactions.recent'))->assertOk()->assertJsonCount(1, 'data');
        $this->getJson(route('api.courses.low-allowance'))->assertOk();
        $this->getJson(route('api.courses.allowance', $this->course))->assertOk()
            ->assertJsonPath('data.remaining', 5);

        auth()->logout();
        $this->getJson(route('api.dashboard.stats'))->assertUnauthorized();
    }

    /** 7. Stock adjustment scenarios */
    public function test_07_adjustment_scenarios(): void
    {
        $post = fn ($n, $reason = 'Encoding error') => $this->actingAs($this->admin)
            ->from(route('adjustments.create'))
            ->post(route('adjustments.store'), [
                'course_id' => $this->course->id, 'counted_remaining' => $n, 'reason' => $reason,
            ]);

        // a) decrease 6 -> 4
        $post(4)->assertRedirect(route('attendance-transactions.index'));
        $this->assertSame(4, $this->course->fresh()->absences_remaining);
        $this->assertDatabaseHas('attendance_transactions', ['type' => 'adjustment', 'quantity' => -2]);

        // b) increase 4 -> 5
        $post(5)->assertSessionHasNoErrors();
        $this->assertSame(5, $this->course->fresh()->absences_remaining);

        // c) same value -> rejected
        $post(5)->assertSessionHasErrors('counted_remaining');

        // d) above allowance -> rejected
        $post(9)->assertSessionHasErrors('counted_remaining');

        // e) negative -> rejected
        $post(-1)->assertSessionHasErrors('counted_remaining');

        // f) invalid reason -> rejected
        $post(3, 'Because')->assertSessionHasErrors('reason');

        // g) adjust to 0 -> allowed, limit reached
        $post(0)->assertSessionHasNoErrors();
        $this->assertTrue($this->course->fresh()->isLimitReached());

        $this->assertSame(3, AttendanceTransaction::where('type', 'adjustment')->count());
    }

    /** 8. Transaction history on the product (course) page */
    public function test_08_history_shows_on_course_page(): void
    {
        $this->record('absence', 2);
        $this->record('restore', 1);

        $this->actingAs($this->student)->get(route('courses.show', $this->course))
            ->assertOk()
            ->assertSee('Absence Recorded')
            ->assertSee('Allowance Restored')
            ->assertDontSee('No transactions recorded for this course yet.');

        $t = AttendanceTransaction::first();
        $this->get(route('attendance-transactions.show', $t))->assertOk()->assertSee('IT301');
        $this->get(route('attendance-transactions.index'))->assertOk()->assertSee('IT301');
    }

    /** 9. Validation rules with invalid input */
    public function test_09_validation_rules(): void
    {
        $url = route('attendance-transactions.store');
        $this->actingAs($this->student);

        $this->post($url, [])->assertSessionHasErrors(['course_id', 'type', 'quantity']);
        $this->post($url, ['course_id' => 9999, 'type' => 'absence', 'quantity' => 1])->assertSessionHasErrors('course_id');
        $this->post($url, ['course_id' => $this->course->id, 'type' => 'steal', 'quantity' => 1])->assertSessionHasErrors('type');
        $this->post($url, ['course_id' => $this->course->id, 'type' => 'adjustment', 'quantity' => 1])->assertSessionHasErrors('type');
        $this->post($url, ['course_id' => $this->course->id, 'type' => 'absence', 'quantity' => 0])->assertSessionHasErrors('quantity');
        $this->post($url, ['course_id' => $this->course->id, 'type' => 'absence', 'quantity' => 'abc'])->assertSessionHasErrors('quantity');
        $this->post($url, ['course_id' => $this->course->id, 'type' => 'absence', 'quantity' => 51])->assertSessionHasErrors('quantity');
        $this->post($url, ['course_id' => $this->course->id, 'type' => 'restore', 'quantity' => 1])->assertSessionHasErrors('quantity'); // nothing used yet
        $this->post($url, ['course_id' => $this->course->id, 'type' => 'absence', 'quantity' => 1,
            'reference_number' => str_repeat('x', 51)])->assertSessionHasErrors('reference_number');
        $this->post($url, ['course_id' => $this->course->id, 'type' => 'absence', 'quantity' => 1,
            'notes' => str_repeat('x', 501)])->assertSessionHasErrors('notes');

        $this->assertDatabaseCount('attendance_transactions', 0);

        // Course form validation
        $this->post(route('courses.store'), ['course_code' => 'IT301', 'category' => 'X', 'units' => 9,
            'grade' => 5, 'attendance_rate' => 150])
            ->assertSessionHasErrors(['course_code', 'course_name', 'category', 'units', 'grade', 'instructor', 'attendance_rate']);

        // Transaction list filters
        $this->get(route('attendance-transactions.index', ['type' => 'bad']))->assertSessionHasErrors('type');
        $this->get(route('attendance-transactions.index', ['date_from' => '2026-10-10', 'date_to' => '2026-10-01']))
            ->assertSessionHasErrors('date_to');
    }

    /** 10. Non-admins cannot use admin-only features */
    public function test_10_admin_only_features(): void
    {
        $this->record('absence', 1);
        $t = AttendanceTransaction::first();

        // guest
        auth()->logout();
        $this->get(route('adjustments.create'))->assertRedirect(route('login'));

        // regular user
        $this->actingAs($this->student);
        $this->get(route('adjustments.create'))->assertForbidden();
        $this->post(route('adjustments.store'), ['course_id' => $this->course->id,
            'counted_remaining' => 0, 'reason' => 'Other'])->assertForbidden();
        $this->delete(route('attendance-transactions.destroy', $t))->assertForbidden();
        $this->assertSame(5, $this->course->fresh()->absences_remaining);
        $this->assertDatabaseCount('attendance_transactions', 1);

        // admin can
        $this->actingAs($this->admin);
        $this->get(route('adjustments.create'))->assertOk();
        $this->delete(route('attendance-transactions.destroy', $t))->assertRedirect();
        $this->assertSame(6, $this->course->fresh()->absences_remaining); // effect reversed
    }
}
