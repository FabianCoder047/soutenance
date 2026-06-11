<?php
/**
 * Si MAIL_USERNAME et MAIL_PASSWORD sont renseignés, les invitations (et autres envois)
 * partent en SMTP STARTTLS (port 587 par défaut). Sinon, en dev : journal uniquement ;
 * en prod : tentative via la fonction mail() de PHP.
 *
 * Gmail : utiliser un « mot de passe d'application » et idéalement MAIL_FROM = la même adresse que MAIL_USERNAME.
 */
$verify = $_ENV['MAIL_VERIFY_PEER'] ?? '1';
return [
    'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
    'port' => (int)($_ENV['MAIL_PORT'] ?? 587),
    'username' => $_ENV['MAIL_USERNAME'] ?? '',
    'password' => $_ENV['MAIL_PASSWORD'] ?? '',
    'from_email' => $_ENV['MAIL_FROM'] ?? ($_ENV['MAIL_USERNAME'] ?? 'noreply@emedia.com'),
    'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'e-Media Support',
    'verify_peer' => filter_var($verify, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
];
