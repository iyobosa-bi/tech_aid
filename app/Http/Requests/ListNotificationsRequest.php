<?php

namespace App\Http\Requests;

use App\Enums\NotificationType;
use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Filters for the "All Notifications" page. Like ListTicketsRequest, they come from the
 * query string (bookmarks, back button), so unknown values fall back to defaults instead
 * of failing validation. Everyone may list their own notifications — the repository only
 * ever queries the signed-in user's.
 */
class ListNotificationsRequest extends FormRequest
{
    public const PER_PAGE_OPTIONS = [10, 20, 50];

    public const DEFAULT_PER_PAGE = 20;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array{status: ?string, type: ?string, from: ?string, to: ?string, per_page: int}
     */
    public function filters(): array
    {
        $status = $this->text('status');
        $perPage = (int) $this->text('per_page');

        return [
            'status' => in_array($status, ['unread', 'read'], true) ? $status : null,
            'type' => NotificationType::tryFrom($this->text('type'))?->value,
            'from' => $this->day('from'),
            'to' => $this->day('to'),
            'per_page' => in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : self::DEFAULT_PER_PAGE,
        ];
    }

    // For the badge on the Filters button.
    public function activeFilterCount(): int
    {
        $filters = $this->filters();

        return count(array_filter([$filters['status'], $filters['type'], $filters['from'], $filters['to']]));
    }

    // A query value as a string — "?type[]=x" arrives as an array and is ignored.
    private function text(string $key): string
    {
        $value = $this->query($key);

        return is_string($value) ? trim($value) : '';
    }

    // A real calendar date in Y-m-d (what <input type="date"> sends), or null.
    private function day(string $key): ?string
    {
        $value = $this->text($key);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }
}
