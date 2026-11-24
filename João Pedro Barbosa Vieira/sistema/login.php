<?php
require_once __DIR__ . '/funcoes.php';
if (esta_logado()) { header('Location: ' . (($_SESSION['usuario_tipo'] ?? '') === 'admin' ? 'dashboard.php' : 'cliente.php')); exit; }
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
        $erro = 'Informe um e-mail válido e sua senha.';
    } else {
        $stmt = $conexao->prepare('SELECT id_usuario, nome, email, senha, tipo, id_tutor FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = (int)$usuario['id_usuario'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo'];
            $_SESSION['usuario_tutor_id'] = $usuario['id_tutor'] ? (int)$usuario['id_tutor'] : null;
            $_SESSION['ultima_atividade'] = time();
            header('Location: ' . ($usuario['tipo'] === 'admin' ? 'dashboard.php' : 'cliente.php')); exit;
        }
        $erro = 'E-mail ou senha incorretos. Verifique os dados e tente novamente.';
    }
}
?>
<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Entrar | VetCare</title><link rel="stylesheet" href="assets/style.css"></head>
<body class="login-page"><section class="login-card"><div class="logo-mark">＋</div><p class="eyebrow">CLÍNICA VETERINÁRIA</p><h1>Bem-vindo ao VetCare</h1><p class="muted">Acesse o sistema para continuar.</p>
<?php if ($erro): ?><div class="alert error"><?= h($erro) ?></div><?php endif; ?>
<?php if (isset($_GET['cadastro'])): ?><div class="alert success">Cadastro realizado! Entre com seu e-mail e senha.</div><?php endif; ?>
<?php if (isset($_GET['expirada'])): ?><div class="alert">Sua sessão expirou por inatividade. Entre novamente.</div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><label>E-mail</label><input type="email" name="email" required autocomplete="username" placeholder="seu@email.com"><label>Senha</label><input type="password" name="senha" required autocomplete="current-password" placeholder="Sua senha"><button class="btn full" type="submit">Entrar no sistema</button></form>
<p class="hint">Demonstração administrador: admin@clinica.com<br>Senha: Clinica@123</p><p class="hint">Ainda não tem conta? <a href="cadastro_cliente.php">Cadastre-se como Tutor</a>.</p></section></body></html>
