<?php

header("Content-Type: application/json");
$input = json_decode(file_get_contents("php://input"), true);

$response = [
    "received" => $input["message"],
    "reply" => "2. Hello from PHP Backend!"
];

echo json_encode($response);
