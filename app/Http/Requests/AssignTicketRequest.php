<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\SupportAssignmentService;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Flow 5: Head of Service Management picks who works on the ticket. Only someone in the
 * bucket qualifies — active Application Support staff who aren't on leave — so a stale page
 * or a hand-edited form can't hand a ticket to anyone else.
 */
class AssignTicketRequest extends FormRequest
{
    public const NOTE_MAX = 1000;

    // Its own bag: the ticket page also has the resolve modal, whose fields must not collide.
    protected $errorBag = 'assign';

    public function authorize(): Response
    {
        return Gate::inspect('assign', $this->route('ticket'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assignee_id' => ['required', 'integer', Rule::in($this->assignableIds())],
            'note' => ['nullable', 'string', 'max:'.self::NOTE_MAX],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'assignee_id.required' => 'Choose who should work on this ticket.',
            'assignee_id.in' => 'That person can\'t take tickets right now (on leave or no longer in Application Support). Please choose someone else.',
        ];
    }

    public function assignee(): User
    {
        return app(UserRepository::class)->findSupportStaff((int) $this->validated('assignee_id'));
    }

    /**
     * @return list<int>
     */
    protected function assignableIds(): array
    {
        return app(SupportAssignmentService::class)->bucket()->pluck('id')->all();
    }
}
