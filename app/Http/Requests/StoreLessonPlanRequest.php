<?php

namespace App\Http\Requests;

use App\Http\Controllers\LessonPlanController;
use Illuminate\Foundation\Http\FormRequest;

class StoreLessonPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth middleware handles access control
    }

    public function rules(): array
    {
        $allowedClasses = implode(',', LessonPlanController::CLASS_NAMES);

        $rules = [
            'class_name'  => "required|string|in:{$allowedClasses}",
            'lesson_day'  => 'required|integer|min:1|max:20',
            'description' => 'nullable|string|max:2000',
            'file'        => [
                'file',
                'max:1024', // 1 MB limit
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,rtf,odt,odp,ods',
            ],
        ];

        // File is required for both new plans and new versions
        if ($this->isMethod('POST') || $this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['file'][] = 'required';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'class_name.required' => 'Please select a class name.',
            'class_name.in'       => 'Please select one of the available class names.',
            'lesson_day.required' => 'Please select a lesson number.',
            'lesson_day.min'      => 'Lesson number must be between 1 and 20.',
            'lesson_day.max'      => 'Lesson number must be between 1 and 20.',
'file.max'            => 'The uploaded file must be smaller than 1 MB.',
            'file.mimes'          => 'Allowed file types: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, RTF, ODT, ODP, ODS.',
            'file.required'       => 'Please attach a file to your lesson plan.',
        ];
    }
}
