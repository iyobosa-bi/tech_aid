<?php

namespace App\Enums;

enum RoleName: string
{
    case Requester = 'Requester';
    case LineManager = 'Line Manager';
    case HeadOfServiceManagement = 'Head of Service Management';
    case ApplicationSupport = 'Application Support';
    case Admin = 'Admin';

    /**
     * @return list<PermissionName>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Requester => [PermissionName::CreateTickets, PermissionName::RespondToResolutions],
            self::LineManager => [PermissionName::ViewTeamTickets, PermissionName::ApproveTickets],
            self::HeadOfServiceManagement => [PermissionName::AssignTickets, PermissionName::ReassignTickets],
            self::ApplicationSupport => [PermissionName::ViewAssignedTickets, PermissionName::ResolveTickets],
            self::Admin => [PermissionName::ManageUsers, PermissionName::ManageSettings],
        };
    }
}
