<?php
require_once '../includes/config.php';
// No requireAdmin() guard — destroying a non-existent session is harmless.
// config.php handles session_start(), so the session is active before destroy.
session_destroy();
redirect('/login.php');
