<?php
session_start();

session_unset();
session_destroy();

// Redirección absoluta
header("Location: ../index.php");
exit();
?>