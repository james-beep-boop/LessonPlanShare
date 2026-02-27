<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLessonPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth middleware handles access control
    }

    public function rules(): array
    {
        $rules = [
            'class_name'  => 'required|string|max:100',
            'lesson_day'  => 'required|integer|min:1|max:20',
            'description' => 'nullable|string|max:2000',
            'file'        => [
                'file',
                'max:1024', // 1 MB limit
                'mimes:doc,docx,txt,rtf,odt',
            ],
        ];

        // File is required for both new plans and new versions
        if ($this->isMethod('POST') || $this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['file'][] = 'required';
        }

        // revision_type is required only when creating a new version (PUT/PATCH).
        // For POST (new plan), the version is always computed as 'major' (or 1.0.0).
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['revision_type'] = ['required', 'string', Rule::in(['major', 'minor'])];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'class_name.required'    => 'Please enter or select a class name.',
            'class_name.max'         => 'Class name must be 100 characters or fewer.',
            'lesson_day.required'    => 'Please select a lesson number.',
            'lesson_day.min'         => 'Lesson number must be between 1 and 20.',
            'lesson_day.max'         => 'Lesson number must be between 1 and 20.',
            'file.max'               => 'The uploaded file must be smaller than 1 MB.',
            'file.mimes'             => 'Allowed file types: DOC, DOCX, TXT, RTF, ODT.',
            'file.required'          => 'Please attach a file to your lesson plan.',
            'revision_type.required' => 'Please select a revision type (major or minor).',
            'revision_type.in'       => 'Revision type must be either "major" or "minor".',
        ];
    }
}
