<?php

namespace App\Http\Requests;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Admin → Users filters. They come from the query string (bookmarks, the back button), so unknown
 * values fall back to "all" instead of failing validation — like ListTicketsRequest.
 */
class ListUsersRequest extends FormRequest
{
    public const STATUSES = ['active', 'deactivated'];

    public function authorize(): Response
    {
        return Gate::inspect('viewAny', User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array{search: string, role: ?string, status: ?string}
     */
    public function filters(): array
    {
        $status = $this->query('status');

        return [
            'search' => Str::limit(trim((string) $this->query('search', '')), 100, ''),
            'role' => RoleName::tryFrom((string) $this->query('role', ''))?->value,
            'status' => in_array($status, self::STATUSES, true) ? $status : null,
        ];
    }
}
