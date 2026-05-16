<?php

// Kill session and redirect to login

session_start();
session_unset();
session_destroy();
header("Location: ../Forms/login_form.php");


exit;