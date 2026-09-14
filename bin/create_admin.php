<?php
declare(strict_types=1);

require __DIR__.'/../app/bootstrap.php';
if (PHP_SAPI !== 'cli') { exit("CLI only\n"); }
$username=$argv[1]??null; $password=$argv[2]??null; $fullName=$argv[3]??'مدیر سامانه';
if(!$username || !$password){fwrite(STDERR,"Usage: php bin/create_admin.php USERNAME PASSWORD [FULL_NAME]\n");exit(1);}
$role=db()->query("SELECT id FROM roles WHERE name='admin'")->fetchColumn(); if(!$role) throw new RuntimeException('نقش admin یافت نشد؛ ابتدا schema.sql را اجرا کنید.');
$st=db()->prepare('INSERT INTO users(username,password_hash,full_name,role_id) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),full_name=VALUES(full_name),role_id=VALUES(role_id),is_active=1');
$st->execute([$username,password_hash($password,PASSWORD_DEFAULT),$fullName,$role]);echo "Admin created/updated: {$username}\n";
