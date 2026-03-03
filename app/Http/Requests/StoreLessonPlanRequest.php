<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a brand-new lesson plan upload (POST /lesson-plans).
 *
 * New-version uploads use StoreVersionRequest instead (POST /lesson-plans/{id}/versions).
 * The PUT/PATCH branching that used to live here for revision_type has been removed;
 * it was dead code since the route split.
 */
class StoreLessonPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth middleware handles access control
    }

    public function rules(): array
    {
        return [
            'class_name'  => 'required|string|max:100',
            'lesson_day'  => 'required|integer|min:1|max:20',
            'description' => 'nullable|string|max:2000',
            'file'        => [
                'required',
                'file',
                'max:1024', // 1 MB limit
                'mimes:doc,docx,txt,rtf,odt',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'class_name.required' => 'Please enter or select a class name.',
            'class_name.max'      => 'Class name must be 100 characters or fewer.',
            'lesson_day.required' => 'Please select a lesson number.',
            'lesson_day.min'      => 'Lesson number must be between 1 and 20.',
            'lesson_day.max'      => 'Lesson number must be between 1 and 20.',
            'file.max'            => 'The uploaded file must be smaller than 1 MB.',
            'file.mimes'          => 'Allowed file types: DOC, DOCX, TXT, RTF, ODT.',
            'file.required'       => 'Please attach a file to your lesson plan.',
        ];
    }
}
