<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ApproveTicketRequest extends FormRequest
{
    public const MAX_COMMENT_LENGTH = 1000;

    // Its own error bag, so an approve error never reopens the decline modal (both use "comment").
    protected $errorBag = 'approve';

    public function authorize(): Response
    {
        return Gate::inspect('approve', $this->route('ticket'));
    }

    /**
     * Flow 3: the comment is optional. A blank one is replaced with
     * TicketDecisionService::DEFAULT_APPROVAL_COMMENT.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'comment' => ['nullable', 'string', 'max:'.self::MAX_COMMENT_LENGTH],
        ];
    }
}
