<?php

namespace App\Policies;

use App\Models\EmailTemplate;
use App\Models\User;

class EmailTemplatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EmailTemplate $template): bool
    {
        return $user->organization_id === $template->organization_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['owner', 'admin', 'marketing']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EmailTemplate $template): bool
    {
        return $user->organization_id === $template->organization_id 
            && in_array($user->role, ['owner', 'admin', 'marketing'])
            && !$template->is_system;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EmailTemplate $template): bool
    {
        return $user->organization_id === $template->organization_id 
            && in_array($user->role, ['owner', 'admin', 'marketing'])
            && !$template->is_system;
    }
}
