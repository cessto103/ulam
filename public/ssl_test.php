<?php
echo 'php.ini: ' . php_ini_loaded_file() . '<br>';
echo 'cainfo: ' . ini_get('curl.cainfo') . '<br>';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.anthropic.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_exec($ch);
echo 'Error: ' . (curl_error($ch) ?: 'none') . '<br>';
curl_close($ch);
