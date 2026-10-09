<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Resolving needs resolution notes: they go to the requester and stay on the ticket. Files are
 * optional — upload ids from the resolve form's dropzone (TicketUploadController::storeForResolution).
 */
class ResolveTicketRequest extends FormRequest
{
    public const NOTES_MIN = 10;

    public const NOTES_MAX = 2000;

    // Its own limit, separate from the requester's files on the ticket.
    public const MAX_FILES = 5;

    protected $errorBag = 'resolve';

    public function authorize(): Response
    {
        return Gate::inspect('resolve', $this->route('ticket'));
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
            'resolution_notes' => ['required', 'string', 'min:'.self::NOTES_MIN, 'max:'.self::NOTES_MAX],
            'attachments' => ['nullable', 'array', 'max:'.self::MAX_FILES],
            'attachments.*' => ['required', 'string', 'uuid', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'resolution_notes.required' => 'Please describe how the issue was resolved.',
            'resolution_notes.min' => 'Please give a little more detail (at least '.self::NOTES_MIN.' characters).',
            'attachments.max' => 'You can attach at most '.self::MAX_FILES.' files.',
            'attachments.*.uuid' => 'One of your files is invalid. Please remove it and upload it again.',
        ];
    }

    /**
     * @return list<string>
     */
    public function uploadIds(): array
    {
        return $this->validated('attachments') ?? [];
    }
}
