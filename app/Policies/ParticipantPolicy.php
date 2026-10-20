<?php

namespace App\Policies;

use App\Enums\UserRole;

class ParticipantPolicy extends RolePolicy
{
    protected array $roles = [UserRole::Admin, UserRole::Cs];
}
