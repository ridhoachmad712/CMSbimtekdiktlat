<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Policy dasar berbasis peran. Turunan cukup menentukan peran yang boleh
 * mengelola model; super_admin selalu diizinkan.
 */
abstract class RolePolicy
{
    /** @var array<UserRole> */
    protected array $roles = [UserRole::Admin];

    /** @var array<UserRole>|null Peran yang boleh menghapus; null = sama dengan. */
    protected ?array $deleteRoles = null;

    public function before(User $user): ?bool
    {
        return $user->role === UserRole::SuperAdmin ? true : null;
    }

    protected function allows(User $user): bool
    {
        return in_array($user->role, $this->roles, true);
    }

    protected function allowsDelete(User $user): bool
    {
        return in_array($user->role, $this->deleteRoles ?? $this->roles, true);
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user);
    }

    public function view(User $user, mixed $model = null): bool
    {
        return $this->allows($user);
    }

    public function create(User $user): bool
    {
        return $this->allows($user);
    }

    public function update(User $user, mixed $model = null): bool
    {
        return $this->allows($user);
    }

    public function reorder(User $user): bool
    {
        return $this->allows($user);
    }

    public function replicate(User $user, mixed $model = null): bool
    {
        return $this->allows($user);
    }

    public function delete(User $user, mixed $model = null): bool
    {
        return $this->allowsDelete($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allowsDelete($user);
    }

    public function restore(User $user, mixed $model = null): bool
    {
        return $this->allowsDelete($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->allowsDelete($user);
    }

    public function forceDelete(User $user, mixed $model = null): bool
    {
        return $this->allowsDelete($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->allowsDelete($user);
    }
}
