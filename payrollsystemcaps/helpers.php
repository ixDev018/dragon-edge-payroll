<?php

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
function base64url_decode($data) {
    $pad = (4 - (strlen($data) % 4)) % 4;
    if ($pad) $data .= str_repeat('=', $pad);
    return base64_decode(strtr($data, '-_', '+/'));
}
