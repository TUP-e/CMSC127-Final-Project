<?php
require_once "../Core/session.php";
requireLogin();

if (!isTreasurer()) {
    die("Access denied");
}
?>

<h1>Treasurer Dashboard</h1>

<button>Add Transaction</button>
<button>Edit</button>
<button>Delete</button>