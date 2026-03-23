<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap/app.php';

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;
$firstName = $argv[3] ?? 'Admin';
$lastName = $argv[4] ?? 'User';

if (!$email || !$password) {
    fwrite(STDERR, "Usage: php scripts/make_admin.php email@example.com StrongPassword [FirstName] [LastName]\n");
    exit(1);
}

$statement = db()->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
$statement->execute(['email' => $email]);
$existingId = $statement->fetchColumn();

if ($existingId) {
    db()->prepare(
        'UPDATE users
         SET role = :role,
             first_name = :first_name,
             last_name = :last_name,
             password_hash = :password_hash,
             is_active = TRUE
         WHERE id = :id'
    )->execute([
        'role' => 'admin',
        'first_name' => $firstName,
        'last_name' => $lastName,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'id' => $existingId,
    ]);

    echo "Updated existing user to admin.\n";
    exit(0);
}

db()->prepare(
    'INSERT INTO users (first_name, last_name, email, password_hash, role, is_active)
     VALUES (:first_name, :last_name, :email, :password_hash, :role, TRUE)'
)->execute([
    'first_name' => $firstName,
    'last_name' => $lastName,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'role' => 'admin',
]);

echo "Created admin user.\n";
