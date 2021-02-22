<?php

class PluginLibresignHook extends CommonDBTM
{
    public static function preItemAdd(TicketValidation $ticket)
    {
        $user = new User();
        $user->getFromDB($ticket->input['users_id_validate']);
        $email = $user->getDefaultEmail();
        if (!$email) {
            $ticket->input = null;
            Session::addMessageAfterRedirect(
                sprintf(
                   __('The selected user (%s) has no valid email address. The request has not been created, without email confirmation.'),
                   $user->getField('name')
                ),
                false,
                ERROR
             );
        }
    }
}