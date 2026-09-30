<?php

namespace App\Http\Requests;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Repositories\TicketRepository;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * List filters come from the query string (bookmarks, shared links, back button),
 * so unknown values fall back to defaults instead of failing validation.
 */
class ListTicketsRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('viewAny', Ticket::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array{search: string, status: ?string, sort: string, direction: string}
     */
    public function filters(): array
    {
        $sort = $this->query('sort');

        return [
            'search' => Str::limit(trim((string) $this->query('search', '')), 100, ''),
            'status' => TicketStatus::tryFrom((string) $this->query('status', ''))?->value,
            'sort' => in_array($sort, TicketRepository::SORTABLE, true) ? $sort : 'created_at',
            'direction' => $this->query('direction') === 'asc' ? 'asc' : 'desc',
        ];
    }
}
