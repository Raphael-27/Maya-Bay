<?php

require_once 'config/firestore.php';

$resultado = Firestore::getAll('habitaciones');

echo "<pre>";
print_r($resultado);
echo "</pre>";