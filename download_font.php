<?php
$ch = curl_init('https://github.com/googlefonts/roboto/raw/main/src/hinted/Roboto-Regular.ttf');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$data = curl_exec($ch);
file_put_contents('storage/app/font.ttf', $data);
echo 'Size: ' . strlen($data);
