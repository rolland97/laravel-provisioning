<?php

declare(strict_types=1);

return [
    'invite' => [
        'subject' => 'Your :app account is ready',
        'greeting' => 'Hello :name,',
        'body' => 'An account has been created for you on :app. Choose your own password using the button below to start signing in.',
        'action' => 'Set your password',
        'expiry' => 'This link is valid for :days days. If it expires, ask your administrator to send a new invite.',
        'heading' => 'Set your password',
        'intro' => 'Choose a password for your :app account to start signing in.',
        'new_password' => 'New password',
        'confirm_password' => 'Confirm password',
        'submit' => 'Set password and sign in',
        'invalid' => 'This invitation link is invalid or has expired. Ask your administrator to send a new invite.',
        'send_action' => 'Send invite',
        'resend_action' => 'Resend invite',
        'sent' => 'Invite sent.',
    ],
    'force_change' => [
        'title' => 'Set a new password',
        'helper' => 'Your password was set by an administrator. Choose your own password to continue.',
        'current_password' => 'Current password',
        'new_password' => 'New password',
        'new_password_confirmation' => 'Confirm new password',
        'submit' => 'Save new password',
        'same_as_current' => 'Your new password must be different from your current password.',
        'saved' => 'Your new password is saved.',
    ],
    'two_factor' => [
        'reset_action' => 'Reset two-factor authentication',
        'reset_confirm' => 'This removes the user\'s authenticator and recovery codes. They will be asked to enrol again at their next sign-in.',
        'reset_success' => 'Two-factor authentication has been reset.',
        'console_reset' => 'Two-factor authentication reset for :email. They will be asked to enrol again at their next sign-in.',
        'console_not_enrolled' => ':email has no two-factor authentication to reset.',
        'console_unknown' => 'No user found for :email.',
    ],
];
