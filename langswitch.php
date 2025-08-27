<?php
$target = $_SERVER['HTTP_REFERER'] ?? '/';
$parts = parse_url($target);
parse_str($parts['query'] ?? '', $params);
if (isset($_GET['lang'])) {
    $params['lang'] = $_GET['lang'];
}
$query = http_build_query($params);
$url = $parts['path'] . ($query ? "?$query" : '');
header("Location: $url");
exit;
