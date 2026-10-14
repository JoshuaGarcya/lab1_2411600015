@extends('layouts.app')

@section('content')

<div class="container" style="max-width: 760px;">

    <div class="pb-2 mb-4 border-bottom">
        <h2 class="mb-1">Record Attendance Transaction</h2>
        <p class="text-muted mb-0">
            Log an absence, or restore allowance (for example, an approved excuse letter).
        </p>
    </div>

    @include('partials.flash')

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="POST" action="{{ route('attendance-transactions.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="course_id" class="form-label">Course</label>
                    <select id="course_id" name="course_id" class="form-select" required>
                        <option value="">Select a course…</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}"
                                    data-remaining="{{ $course->absences_remaining }}"
                                    data-allowed="{{ $course->absences_allowed }}"
                                    @selected(old('course_id', $selectedCourse) == $course->id)>
                                {{ $course->course_code }} — {{ $course->course_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('course_id')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror

                    <div id="allowance-info" class="form-text"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label d-block">Transaction Type</label>

                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="type" id="type_absence"
                               value="absence" @checked(old('type', 'absence') === 'absence')>
                        <label class="form-check-label" for="type_absence">Absence (uses allowance)</label>
                    </div>

                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="type" id="type_restore"
                               value="restore" @checked(old('type') === 'restore')>
                        <label class="form-check-label" for="type_restore">Restore (excused / makeup)</label>
                    </div>

                    @error('type')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="quantity" class="form-label">Number of absences</label>
                    <input type="number" id="quantity" name="quantity" class="form-control"
                           min="1" value="{{ old('quantity', 1) }}" required>
                    @error('quantity')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="reference_number" class="form-label">Reference Number (optional)</label>
                    <input type="text" id="reference_number" name="reference_number" class="form-control"
                           maxlength="50" value="{{ old('reference_number') }}"
                           placeholder="Example: Excuse letter #12, class of Oct 3">
                    @error('reference_number')
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
                    <button type="submit" id="submit-btn" class="btn btn-primary">Save Transaction</button>
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
        const course = document.getElementById('course_id');
        const quantity = document.getElementById('quantity');
        const info = document.getElementById('allowance-info');
        const submit = document.getElementById('submit-btn');
        const types = document.querySelectorAll('input[name="type"]');

        function selectedType() {
            return document.querySelector('input[name="type"]:checked').value;
        }

        // Shows the current allowance and caps the quantity for the chosen type
        function refresh() {
            const option = course.options[course.selectedIndex];

            if (!option || !option.dataset.remaining) {
                info.textContent = '';
                quantity.removeAttribute('max');
                submit.disabled = false;
                return;
            }

            const remaining = parseInt(option.dataset.remaining, 10);
            const allowed = parseInt(option.dataset.allowed, 10);
            const used = allowed - remaining;
            const max = selectedType() === 'absence' ? remaining : used;

            info.textContent = 'Current allowance: ' + remaining + ' of ' + allowed +
                ' absences remaining. Maximum for this transaction: ' + max + '.';

            quantity.max = max;
            quantity.min = max > 0 ? 1 : 0;

            if (max === 0) {
                info.textContent += ' Nothing to ' + (selectedType() === 'absence' ? 'record' : 'restore') + '.';
                submit.disabled = true;
            } else {
                submit.disabled = false;

                if (parseInt(quantity.value, 10) > max) {
                    quantity.value = max;
                }
            }
        }

        course.addEventListener('change', refresh);
        types.forEach(function (t) { t.addEventListener('change', refresh); });
        refresh();
    })();
</script>
@endpush
