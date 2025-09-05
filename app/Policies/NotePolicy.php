<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class NotePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Note $note)
    {
        return $note->user_id === $user->id;
    }


    // /**
    //  * Determine whether the user can create models.
    //  */
    // public function create(User $user, Note $note)
    // {
    //     return $note->user_id === $user->id;
    // }


    // /**
    //  * Determine whether the user can view any models.
    //  */
    // public function viewAny(User $user, Note $note)
    // {
    //     return $note->user_id === $user->id;
    // }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Note $note)
    {
        return $note->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Note $note)
    {
        return $note->user_id === $user->id;
    }

}
