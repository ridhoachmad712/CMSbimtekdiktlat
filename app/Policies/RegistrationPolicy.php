<?php

namespace App\Policies;

use App\Enums\UserRole;

/** CS boleh mengelola pendaftaran, tetapi penghapusan hanya untuk admin. */
class RegistrationPolicy extends RolePolicy
{
    protected array $roles = [UserRole::Admin, UserRole::Cs];

    protected ?array $deleteRoles = [UserRole::Admin];
}
