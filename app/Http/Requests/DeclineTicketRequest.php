<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeclineTicketRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('decline', $this->route('ticket'));
    }

    /**
     * Flow 3: a decline must say why — the comment goes to the requester.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'comment.required' => 'Please explain why you are declining this ticket.',
            'comment.min' => 'Please give a little more detail (at least 5 characters).',
        ];
    }
}
