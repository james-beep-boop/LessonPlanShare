<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the "Upload New Version" form submission.
 *
 * Distinct from StoreLessonPlanRequest (first upload) because:
 * - File is always required (no optional-on-update logic needed).
 * - revision_type is always required (major or minor).
 * These requirements are unconditional, so no isMethod() branching is needed.
 */
class StoreVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Auth middleware ensures the user is logged in and verified.
        // LessonPlanPolicy is not applied here — this form is open to any
        // verified user (different-author uploads are allowed, creating a fork).
        return true;
    }

    public function rules(): array
    {
        return [
            'class_name'    => 'required|string|max:100',
            'lesson_day'    => 'required|integer|min:1|max:20',
            'description'   => 'nullable|string|max:2000',
            'file'          => ['required', 'file', 'max:1024', 'mimes:doc,docx,txt,rtf,odt'],
            'revision_type' => ['required', 'string', Rule::in(['major', 'minor'])],
        ];
    }

    public function messages(): array
    {
        return [
            'class_name.required'    => 'Please enter or select a class name.',
            'class_name.max'         => 'Class name must be 100 characters or fewer.',
            'lesson_day.required'    => 'Please select a lesson number.',
            'lesson_day.min'         => 'Lesson number must be between 1 and 20.',
            'lesson_day.max'         => 'Lesson number must be between 1 and 20.',
            'file.required'          => 'Please attach a revised lesson plan file.',
            'file.max'               => 'The uploaded file must be smaller than 1 MB.',
            'file.mimes'             => 'Allowed file types: DOC, DOCX, TXT, RTF, ODT.',
            'revision_type.required' => 'Please select a revision type (major or minor).',
            'revision_type.in'       => 'Revision type must be either "major" or "minor".',
        ];
    }
}
