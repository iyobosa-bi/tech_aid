<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Gate;

/**
 * Flow 4 — editing a returned ticket and resubmitting it. Same fields as creating one,
 * plus an optional note for the line manager. New files can be added up to the overall limit.
 */
class UpdateTicketRequest extends StoreTicketRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('resubmit', $this->ticket());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'note' => ['nullable', 'string', 'max:1000'],
            'attachments' => ['nullable', 'array', 'max:'.$this->remainingAttachmentSlots()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'attachments.max' => 'This ticket already has '.$this->ticket()->attachments()->count().' files; you can add '.$this->remainingAttachmentSlots().' more.',
        ];
    }

    private function remainingAttachmentSlots(): int
    {
        return max(0, self::MAX_ATTACHMENTS - $this->ticket()->attachments()->count());
    }

    private function ticket(): Ticket
    {
        return $this->route('ticket');
    }
}
