<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'lesson_day'  => 'required|integer|min:1|max:999',
            'description' => 'nullable|string|max:2000',
            'file'        => [
                'file',
                'max:10240', // 10 MB limit for DreamHost shared hosting
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
            'class_name.required' => 'Please enter the class name (e.g., AP Biology, Algebra II).',
            'lesson_day.required' => 'Please enter the lesson day number.',
            'file.max'            => 'The uploaded file must be smaller than 10 MB.',
            'file.mimes'          => 'Allowed file types: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, RTF, ODT, ODP, ODS.',
            'file.required'       => 'Please attach a file to your lesson plan.',
        ];
    }
}
