<?php
declare(strict_types=1);

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3307); 
define('DB_NAME', 'clinica_veterinaria');
define('DB_USER', 'root');
define('DB_PASS', '');


define('APP_ENCRYPTION_KEY', getenv('CLINICA_APP_KEY') ?: 'troque-esta-chave-local-por-32-bytes!!');

define('SESSION_TIMEOUT', 900); // 15 minutos de inatividade.
date_default_timezone_set('America/Recife');
