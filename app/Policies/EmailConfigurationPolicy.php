<?php

namespace App\Policies;

use App\Models\User;
use App\Models\EmailConfiguration;

class EmailConfigurationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Todos los usuarios autenticados pueden ver sus configuraciones
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EmailConfiguration $emailConfiguration): bool
    {
        return $user->empresa_id === $emailConfiguration->empresa_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Todos los usuarios pueden crear configuraciones para su empresa
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EmailConfiguration $emailConfiguration): bool
    {
        return $user->empresa_id === $emailConfiguration->empresa_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EmailConfiguration $emailConfiguration): bool
    {
        return $user->empresa_id === $emailConfiguration->empresa_id;
    }
}
