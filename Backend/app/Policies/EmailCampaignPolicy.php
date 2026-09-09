<?php

namespace App\Policies;

use App\Models\EmailCampaign;
use App\Models\User;

class EmailCampaignPolicy
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
    public function view(User $user, EmailCampaign $campaign): bool
    {
        return $user->organization_id === $campaign->organization_id;
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
    public function update(User $user, EmailCampaign $campaign): bool
    {
        return $user->organization_id === $campaign->organization_id 
            && in_array($user->role, ['owner', 'admin', 'marketing']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EmailCampaign $campaign): bool
    {
        return $user->organization_id === $campaign->organization_id 
            && in_array($user->role, ['owner', 'admin', 'marketing']);
    }
}
