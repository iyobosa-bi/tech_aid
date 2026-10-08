<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Flow 6: the same choice as assigning, except it must be someone other than the person
 * who has the ticket now.
 */
class ReassignTicketRequest extends AssignTicketRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('reassign', $this->route('ticket'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        array_splice($rules['assignee_id'], 2, 0, [Rule::notIn([$this->route('ticket')->assigned_to_id])]);

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'assignee_id.not_in' => 'They already have this ticket. Choose someone else.',
        ];
    }
}
