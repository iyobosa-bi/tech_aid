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
}
