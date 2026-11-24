<?php
require_once __DIR__ . '/conexao.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_start();
}

function h(?string $valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}
function esta_logado(): bool {
    return isset($_SESSION['usuario_id']);
}
function exigir_admin(): void {
    exigir_login();
    if (($_SESSION['usuario_tipo'] ?? '') !== 'admin') {
        header('Location: cliente.php');
        exit;
    }
}
function exigir_cliente(): void {
    exigir_login();
    if (($_SESSION['usuario_tipo'] ?? '') !== 'usuario') {
        header('Location: dashboard.php');
        exit;
    }
}
function exigir_login(): void {
    if (!esta_logado()) {
        header('Location: login.php');
        exit;
    }
    $agora = time();
    if (isset($_SESSION['ultima_atividade']) && ($agora - $_SESSION['ultima_atividade']) > SESSION_TIMEOUT) {
        $_SESSION = [];
        session_destroy();
        header('Location: login.php?expirada=1');
        exit;
    }
    $_SESSION['ultima_atividade'] = $agora;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function validar_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
            http_response_code(403);
            exit('Solicitação inválida. Atualize a página e tente novamente.');
        }
    }
}
function criptografar_cpf(string $cpf): string {
    $chave = hash('sha256', APP_ENCRYPTION_KEY, true);
    $iv = random_bytes(12);
    $tag = '';
    $cifra = openssl_encrypt($cpf, 'aes-256-gcm', $chave, OPENSSL_RAW_DATA, $iv, $tag);
    if ($cifra === false) {
        throw new RuntimeException('Falha ao proteger o CPF.');
    }
    return base64_encode($iv . $tag . $cifra);
}
function descriptografar_cpf(?string $valor): string {
    if (!$valor) return '';
    $dados = base64_decode($valor, true);
    if ($dados === false || strlen($dados) < 28) return '';
    $chave = hash('sha256', APP_ENCRYPTION_KEY, true);
    $iv = substr($dados, 0, 12);
    $tag = substr($dados, 12, 16);
    $cifra = substr($dados, 28);
    $resultado = openssl_decrypt($cifra, 'aes-256-gcm', $chave, OPENSSL_RAW_DATA, $iv, $tag);
    return $resultado === false ? '' : $resultado;
}
function layout_inicio(string $titulo): void {
    exigir_login();
    $nome = h($_SESSION['usuario_nome'] ?? 'Usuário');
    echo '<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.h($titulo).' | VetCare</title><link rel="stylesheet" href="assets/style.css"></head><body>';
    echo '<header class="topbar"><a class="brand" href="dashboard.php">VetCare <span>Clínica Veterinária</span></a><div class="user-area">Olá, '.$nome.' <a class="logout" href="logout.php">Sair</a></div></header>';
    if (($_SESSION['usuario_tipo'] ?? '') === 'admin') {
        echo '<div class="shell"><aside class="sidebar"><a href="dashboard.php">Visão geral</a><a href="tutores.php">Tutores</a><a href="pets.php">Pets</a><a href="agendamentos.php">Agendamentos</a></aside><main class="content">';
    } else {
        echo '<div class="shell"><aside class="sidebar"><a href="cliente.php">Meu painel</a><a href="meus_pets.php">Meus pets</a><a href="solicitar_agendamento.php">Solicitar consulta</a></aside><main class="content">';
    }
}
function layout_fim(): void {
    echo '</main></div></body></html>';
}
