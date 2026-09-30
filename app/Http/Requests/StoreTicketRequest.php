<?php

namespace App\Http\Requests;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Models\Ticket;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public const MAX_ATTACHMENTS = 5;

    // Runs before validation, so an unauthorized user is denied rather than shown form
    // errors. Returning the policy's Response (not a bool) keeps its denial message.
    public function authorize(): Response
    {
        return Gate::inspect('create', Ticket::class);
    }

    // FilePond can leave an empty hidden input for a file that never finished uploading.
    protected function prepareForValidation(): void
    {
        if (is_array($this->input('attachments'))) {
            $this->merge(['attachments' => array_values(array_filter($this->input('attachments')))]);
        }
    }
    
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'category' => ['required', Rule::enum(TicketCategory::class)],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'attachments' => ['nullable', 'array', 'max:'.self::MAX_ATTACHMENTS],
            'attachments.*' => ['required', 'string', 'uuid', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.max' => 'You can attach at most '.self::MAX_ATTACHMENTS.' files.',
            'attachments.*.uuid' => 'One of your attachments is invalid. Please remove it and upload it again.',
        ];
    }
}
