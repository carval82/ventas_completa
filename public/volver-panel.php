<?php
// Este archivo sirve como puente para volver al panel principal
session_start();

// Redirigir al panel principal
header("Location: /home");
exit;
?>
