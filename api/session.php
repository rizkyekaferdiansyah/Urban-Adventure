<?php
require __DIR__ . '/bootstrap.php';
jsonResponse(true,'', ['user'=>user(),'csrf'=>csrfToken()]);
