<?php

namespace Database\Factories;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_number' => 'TA-'.Str::upper(Str::random(8)),
            'requester_id' => User::factory(),
            'line_manager_id' => User::factory(),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(TicketCategory::cases())->value,
            'priority' => fake()->randomElement(TicketPriority::cases())->value,
            'status' => TicketStatus::PendingLineManagerApproval->value,
        ];
    }

    public function status(TicketStatus $status): static
    {
        return $this->state(fn () => ['status' => $status->value]);
    }
}
