<?php
session_start();
session_destroy();
header("Location: index.php"); // Ili index.php ako želiš
exit;
?>