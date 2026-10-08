<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Resolving needs resolution notes: they go to the requester and stay on the ticket.
 */
class ResolveTicketRequest extends FormRequest
{
    public const NOTES_MIN = 10;

    public const NOTES_MAX = 2000;

    protected $errorBag = 'resolve';

    public function authorize(): Response
    {
        return Gate::inspect('resolve', $this->route('ticket'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resolution_notes' => ['required', 'string', 'min:'.self::NOTES_MIN, 'max:'.self::NOTES_MAX],
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
        ];
    }
}
