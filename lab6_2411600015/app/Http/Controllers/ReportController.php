<?php

namespace App\Http\Controllers;

use App\Models\AttendanceTransaction;
use App\Models\Course;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private const REPORTS = [
        'movement' => 'Attendance Movement Report',
        'low' => 'Low Allowance Report',
        'utilization' => 'Allowance Utilization Report',
    ];

    public function index(Request $request)
    {
        [$report, $from, $to] = $this->parse($request);

        return view('reports.index', [
            'reports' => self::REPORTS,
            'report' => $report,
            'from' => $from,
            'to' => $to,
            'data' => $this->build($report, $from, $to),
        ]);
    }

    public function export(Request $request)
    {
        [$report, $from, $to] = $this->parse($request);

        $data = $this->build($report, $from, $to);
        $filename = "{$report}-report-" . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $data['headings']);

            foreach ($data['rows'] as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function parse(Request $request): array
    {
        $request->validate([
            'report' => 'nullable|in:' . implode(',', array_keys(self::REPORTS)),
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        return [
            $request->filled('report') ? $request->query('report') : 'movement',
            $request->filled('from') ? $request->query('from') : now()->subDays(30)->toDateString(),
            $request->filled('to') ? $request->query('to') : now()->toDateString(),
        ];
    }

    /**
     * Every report returns the same shape so one view (and the CSV export)
     * can render any of them: title, headings, rows and summary.
     */
    private function build(string $report, string $from, string $to): array
    {
        return match ($report) {
            'low' => $this->lowAllowance(),
            'utilization' => $this->utilization(),
            default => $this->movement($from, $to),
        };
    }

    private function movement(string $from, string $to): array
    {
        $transactions = AttendanceTransaction::with(['course', 'user'])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->recent()
            ->get();

        return [
            'title' => self::REPORTS['movement'],
            'headings' => ['Date', 'Course', 'Type', 'Quantity', 'Reference', 'Reason', 'Recorded By'],
            'rows' => $transactions->map(fn ($t) => [
                $t->created_at->format('Y-m-d H:i'),
                optional($t->course)->course_code,
                $t->type_label,
                $t->signed_quantity,
                $t->reference_number ?? '',
                $t->reason ?? '',
                optional($t->user)->name ?? 'Deleted user',
            ])->all(),
            'summary' => [
                'Transactions' => $transactions->count(),
                'Absences used' => abs($transactions->where('quantity', '<', 0)->sum('quantity')),
                'Allowance restored' => $transactions->where('quantity', '>', 0)->sum('quantity'),
            ],
        ];
    }

    private function lowAllowance(): array
    {
        $courses = Course::lowAllowance()->orderBy('absences_remaining')->get();

        return [
            'title' => self::REPORTS['low'],
            'headings' => ['Code', 'Course', 'Instructor', 'Remaining', 'Allowed', 'Warning Level', 'Status', 'Attendance'],
            'rows' => $courses->map(fn ($c) => [
                $c->course_code,
                $c->course_name,
                $c->instructor,
                $c->absences_remaining,
                $c->absences_allowed,
                $c->warning_level,
                $c->allowance_status,
                $c->attendance_rate . '%',
            ])->all(),
            'summary' => [
                'Courses at or below warning level' => $courses->count(),
                'Limit reached' => $courses->filter->isLimitReached()->count(),
            ],
        ];
    }

    private function utilization(): array
    {
        $courses = Course::orderBy('course_code')->get();

        $allowed = $courses->sum('absences_allowed');
        $used = $courses->sum('absences_used');

        return [
            'title' => self::REPORTS['utilization'],
            'headings' => ['Code', 'Course', 'Allowed', 'Used', 'Remaining', '% Used', 'Attendance', 'Status'],
            'rows' => $courses->map(fn ($c) => [
                $c->course_code,
                $c->course_name,
                $c->absences_allowed,
                $c->absences_used,
                $c->absences_remaining,
                $c->absences_allowed > 0 ? round($c->absences_used / $c->absences_allowed * 100) . '%' : '0%',
                $c->attendance_rate . '%',
                $c->allowance_status,
            ])->all(),
            'summary' => [
                'Total allowed' => $allowed,
                'Total used' => $used,
                'Overall % used' => $allowed > 0 ? round($used / $allowed * 100) . '%' : '0%',
            ],
        ];
    }
}
