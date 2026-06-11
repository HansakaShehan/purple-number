<?php
require 'api/game/helpers.php';
$cats = generateCategories(52, null);
echo json_encode($cats, JSON_PRETTY_PRINT);
?>
