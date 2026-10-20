<?php

declare(strict_types=1);

return [
    'invite' => [
        'subject' => 'Akaun :app anda telah sedia',
        'greeting' => 'Salam :name,',
        'body' => 'Satu akaun telah dicipta untuk anda pada :app. Pilih kata laluan anda sendiri menggunakan butang di bawah untuk mula masuk.',
        'action' => 'Tetapkan kata laluan anda',
        'expiry' => 'Pautan ini sah selama :days hari. Jika ia luput, minta pentadbir anda menghantar jemputan baharu.',
        'heading' => 'Tetapkan kata laluan anda',
        'intro' => 'Pilih kata laluan untuk akaun :app anda untuk mula masuk.',
        'new_password' => 'Kata laluan baharu',
        'confirm_password' => 'Sahkan kata laluan',
        'submit' => 'Tetapkan kata laluan dan masuk',
        'invalid' => 'Pautan jemputan ini tidak sah atau telah luput. Minta pentadbir anda menghantar jemputan baharu.',
        'send_action' => 'Hantar jemputan',
        'resend_action' => 'Hantar semula jemputan',
        'sent' => 'Jemputan telah dihantar.',
    ],
    'force_change' => [
        'title' => 'Tetapkan kata laluan baharu',
        'helper' => 'Kata laluan anda ditetapkan oleh pentadbir. Pilih kata laluan anda sendiri untuk meneruskan.',
        'current_password' => 'Kata laluan semasa',
        'new_password' => 'Kata laluan baharu',
        'new_password_confirmation' => 'Sahkan kata laluan baharu',
        'submit' => 'Simpan kata laluan baharu',
        'same_as_current' => 'Kata laluan baharu anda mesti berbeza daripada kata laluan semasa anda.',
        'saved' => 'Kata laluan baharu anda telah disimpan.',
    ],
    'two_factor' => [
        'reset_action' => 'Tetapkan semula pengesahan dua faktor',
        'reset_confirm' => 'Ini membuang aplikasi pengesah dan kod pemulihan pengguna tersebut. Mereka akan diminta mendaftar semula pada kali berikutnya mereka masuk.',
        'reset_success' => 'Pengesahan dua faktor telah ditetapkan semula.',
        'console_reset' => 'Pengesahan dua faktor ditetapkan semula untuk :email. Mereka akan diminta mendaftar semula pada kali berikutnya mereka masuk.',
        'console_not_enrolled' => ':email tiada pengesahan dua faktor untuk ditetapkan semula.',
        'console_unknown' => 'Tiada pengguna ditemui untuk :email.',
    ],
];
