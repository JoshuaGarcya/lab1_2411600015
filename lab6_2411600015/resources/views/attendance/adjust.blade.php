@extends('layouts.app')

@section('content')

<div class="container" style="max-width: 760px;">

    <div class="pb-2 mb-4 border-bottom">
        <h2 class="mb-1">Adjust Absence Count</h2>
        <p class="text-muted mb-0">
            Use this when the instructor's record doesn't match the system. The difference is logged as an adjustment.
        </p>
    </div>

    @include('partials.flash')

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="POST" action="{{ route('adjustments.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="course_id" class="form-label">Course</label>
                    <select id="course_id" name="course_id" class="form-select" required>
                        <option value="">Select a course…</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>
                                {{ $course->course_code }} — {{ $course->course_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('course_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="system_remaining" class="form-label">System: absences remaining</label>
                        <input type="text" id="system_remaining" class="form-control" value="—" readonly>
                    </div>

                    <div class="col-md-6">
                        <label for="counted_remaining" class="form-label">Recorded: absences remaining</label>
                        <input type="number" id="counted_remaining" name="counted_remaining"
                               class="form-control" min="0" value="{{ old('counted_remaining') }}" required>
                        @error('counted_remaining')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div id="difference" class="alert alert-secondary py-2 d-none"></div>

                <div class="mb-3">
                    <label for="reason" class="form-label">Reason for adjustment</label>
                    <select id="reason" name="reason" class="form-select" required>
                        <option value="">Select a reason…</option>
                        @foreach($reasons as $reason)
                            <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ $reason }}</option>
                        @endforeach
                    </select>
                    @error('reason')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="notes" class="form-label">Notes (optional)</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="500"
                              class="form-control">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Adjustment</button>
                    <a href="{{ route('attendance-transactions.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>

        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    (function () {
        const urlTemplate = @json(route('api.courses.allowance', ['course' => '__ID__'], false));
        const course = document.getElementById('course_id');
        const system = document.getElementById('system_remaining');
        const counted = document.getElementById('counted_remaining');
        const diffBox = document.getElementById('difference');
        let systemValue = null;

        function showDifference() {
            if (systemValue === null || counted.value === '') {
                diffBox.classList.add('d-none');
                return;
            }

            const diff = parseInt(counted.value, 10) - systemValue;

            diffBox.classList.remove('d-none');
            diffBox.textContent = diff === 0
                ? 'No difference — nothing to adjust.'
                : 'Difference: ' + (diff > 0 ? '+' : '') + diff + ' (this will be logged as the adjustment).';
        }

        // Load the current system value over AJAX
        function loadAllowance() {
            systemValue = null;
            system.value = '—';
            counted.removeAttribute('max');

            if (!course.value) {
                showDifference();
                return;
            }

            fetch(urlTemplate.replace('__ID__', course.value), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
                .then(function (json) {
                    systemValue = json.data.remaining;
                    system.value = json.data.remaining + ' of ' + json.data.allowed;
                    counted.max = json.data.allowed;
                    showDifference();
                })
                .catch(function () {
                    system.value = 'Could not load';
                });
        }

        course.addEventListener('change', loadAllowance);
        counted.addEventListener('input', showDifference);

        if (course.value) { loadAllowance(); }
    })();
</script>
@endpush
