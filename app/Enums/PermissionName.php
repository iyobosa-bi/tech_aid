<?php

namespace App\Enums;

enum PermissionName: string
{
    case CreateTickets = 'create tickets';
    case RespondToResolutions = 'respond to resolutions';
    case ViewTeamTickets = 'view team tickets';
    case ApproveTickets = 'approve tickets';
    case AssignTickets = 'assign tickets';
    case ReassignTickets = 'reassign tickets';
    case ViewAssignedTickets = 'view assigned tickets';
    case ResolveTickets = 'resolve tickets';
    case ManageUsers = 'manage users';
    case ManageSettings = 'manage settings';

    /**
     * Permissions held by people who work on other people's tickets, as opposed to raising their own.
     *
     * @return list<self>
     */
    public static function handling(): array
    {
        return [
            self::ViewTeamTickets,
            self::ApproveTickets,
            self::AssignTickets,
            self::ReassignTickets,
            self::ViewAssignedTickets,
            self::ResolveTickets,
        ];
    }
}
