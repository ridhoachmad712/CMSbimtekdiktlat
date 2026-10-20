<?php

namespace App\Policies;

/** Manajemen pengguna hanya untuk super_admin (lihat RolePolicy::before). */
class UserPolicy extends RolePolicy
{
    protected array $roles = [];
}
