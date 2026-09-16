@csrf

<div class="row g-3">

    <div class="col-md-6">
        <label for="course_code" class="form-label">
            Course Code
        </label>

        <input
            type="text"
            id="course_code"
            name="course_code"
            class="form-control"
            value="{{ old('course_code', $course->course_code ?? '') }}"
            @if(isset($course)) readonly @endif
            placeholder="Example: IT101"
        >

        @error('course_code')
            <div class="text-danger small mt-1">
                {{ $message }}
            </div>
        @enderror
    </div>


    <div class="col-md-6">
        <label for="course_name" class="form-label">
            Course Name
        </label>

        <input
            type="text"
            id="course_name"
            name="course_name"
            class="form-control"
            value="{{ old('course_name', $course->course_name ?? '') }}"
            placeholder="Example: Web Systems and Technologies"
        >

        @error('course_name')
            <div class="text-danger small mt-1">
                {{ $message }}
            </div>
        @enderror
    </div>


    <div class="col-md-6">
        <label for="category" class="form-label">
            Category
        </label>

        <select id="category" name="category" class="form-select">

            @foreach(['Major', 'Minor', 'Gen Ed', 'Elective'] as $cat)

                <option
                    value="{{ $cat }}"
                    @selected(old('category', $course->category ?? '') === $cat)
                >
                    {{ $cat }}
                </option>

            @endforeach

        </select>

        @error('category')
            <div class="text-danger small mt-1">
                {{ $message }}
            </div>
        @enderror
    </div>


    <div class="col-md-3">
        <label for="units" class="form-label">
            Units
        </label>

        <input
            type="number"
            id="units"
            name="units"
            class="form-control"
            min="1"
            max="6"
            value="{{ old('units', $course->units ?? '') }}"
            placeholder="3"
        >

        @error('units')
            <div class="text-danger small mt-1">
                {{ $message }}
            </div>
        @enderror
    </div>


    <div class="col-md-3">
        <label for="grade" class="form-label">
            Grade
        </label>

        <input
            type="number"
            id="grade"
            name="grade"
            class="form-control"
            min="0"
            max="4"
            step="0.01"
            value="{{ old('grade', $course->grade ?? '') }}"
            placeholder="3.50"
        >

        @error('grade')
            <div class="text-danger small mt-1">
                {{ $message }}
            </div>
        @enderror
    </div>


    <div class="col-md-6">
        <label for="instructor" class="form-label">
            Instructor
        </label>

        <input
            type="text"
            id="instructor"
            name="instructor"
            class="form-control"
            value="{{ old('instructor', $course->instructor ?? '') }}"
            placeholder="Example: Prof. Santos"
        >

        @error('instructor')
            <div class="text-danger small mt-1">
                {{ $message }}
            </div>
        @enderror
    </div>


    <div class="col-md-6">
        <label for="attendance_rate" class="form-label">
            Attendance Rate
        </label>

        <div class="input-group">

            <input
                type="number"
                id="attendance_rate"
                name="attendance_rate"
                class="form-control"
                min="0"
                max="100"
                value="{{ old('attendance_rate', $course->attendance_rate ?? '') }}"
                placeholder="95"
            >

            <span class="input-group-text">
                %
            </span>

        </div>

        @error('attendance_rate')
            <div class="text-danger small mt-1">
                {{ $message }}
            </div>
        @enderror
    </div>

</div>


<div class="mt-4 pt-3 border-top">

    <button type="submit" class="btn btn-primary px-4">
        {{ $buttonText ?? 'Save Course' }}
    </button>

    <a href="{{ route('courses.index') }}" class="btn btn-secondary ms-2">
        Cancel
    </a>

</div>