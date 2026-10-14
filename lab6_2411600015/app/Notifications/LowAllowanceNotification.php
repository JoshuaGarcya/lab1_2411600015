<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowAllowanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Course $course)
    {
        // Don't dispatch the job until the surrounding DB transaction commits
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $limitReached = $this->course->absences_remaining <= 0;

        return (new MailMessage)
            ->subject(($limitReached ? 'Absence limit reached: ' : 'Low absence allowance: ') . $this->course->course_code)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->message())
            ->line('Course: ' . $this->course->course_code . ' — ' . $this->course->course_name)
            ->line('Instructor: ' . $this->course->instructor)
            ->line('Remaining absences: ' . $this->course->absences_remaining
                . ' of ' . $this->course->absences_allowed
                . ' (warning level: ' . $this->course->warning_level . ')')
            ->action('View Course', url('/courses/' . $this->course->id))
            ->line('Suggested action: attend upcoming sessions, or submit an excuse letter if an absence qualifies.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'course_id' => $this->course->id,
            'course_code' => $this->course->course_code,
            'course_name' => $this->course->course_name,
            'instructor' => $this->course->instructor,
            'remaining' => $this->course->absences_remaining,
            'allowed' => $this->course->absences_allowed,
            'warning_level' => $this->course->warning_level,
            'limit_reached' => $this->course->absences_remaining <= 0,
            'message' => $this->message(),
            // Relative path: queued jobs don't know the real host name
            'url' => route('courses.show', $this->course, false),
        ];
    }

    private function message(): string
    {
        if ($this->course->absences_remaining <= 0) {
            return $this->course->course_code . ' has reached its absence limit. Any further absence puts the course at risk.';
        }

        return $this->course->course_code . ' has only ' . $this->course->absences_remaining
            . ' absence(s) left (warning level: ' . $this->course->warning_level . ').';
    }
}
