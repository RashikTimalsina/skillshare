<?php
$adminPassword = "admin1234";
$hash = password_hash($adminPassword, PASSWORD_BCRYPT);
echo $hash;
