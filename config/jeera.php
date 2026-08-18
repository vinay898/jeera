<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ticket Form Version
    |--------------------------------------------------------------------------
    |
    | This option controls which version of the ticket creation form to use.
    | Set to 'classic' for the original multi-grid layout, or 'modern' for
    | the new two-column minimal design.
    |
    | Supported: "classic", "modern"
    |
    */

    'ticket_form_version' => env('TICKET_FORM_VERSION', 'classic'),

    /*
    |--------------------------------------------------------------------------
    | Beta Invite Code
    |--------------------------------------------------------------------------
    |
    | During beta, new users must enter this invite code to register.
    | Leave empty to allow open registration (no code required).
    | Team invitations bypass this requirement.
    |
    */

    'beta_invite_code' => env('BETA_INVITE_CODE'),

];
