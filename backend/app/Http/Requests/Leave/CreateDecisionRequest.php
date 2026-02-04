<?php

namespace App\Http\Requests\Leave;

use App\Models\Leave\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;

class CreateDecisionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'leave_request_id' => [
                'required',
                'uuid',
                'exists:leave_requests,id',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'leave_request_id' => 'طلب الإجازة',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'leave_request_id.required' => 'يجب تحديد طلب الإجازة',
            'leave_request_id.exists' => 'طلب الإجازة غير موجود',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->leave_request_id) {
                $leaveRequest = LeaveRequest::find($this->leave_request_id);

                if ($leaveRequest && $leaveRequest->status !== 'form_completed') {
                    $validator->errors()->add(
                        'leave_request_id',
                        'لا يمكن إنشاء قرار إجازة إلا بعد اكتمال نموذج الطلب'
                    );
                }

                // التحقق من عدم وجود قرار مسبق
                if ($leaveRequest && $leaveRequest->decision()->exists()) {
                    $validator->errors()->add(
                        'leave_request_id',
                        'يوجد قرار إجازة مسبق لهذا الطلب'
                    );
                }
            }
        });
    }
}
