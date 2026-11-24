<?php
require_once __DIR__ . '/funcoes.php';
if (esta_logado()) { header('Location: cliente.php'); exit; }
$erro='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    validar_csrf();
    $nome=trim($_POST['nome']??''); $cpf=preg_replace('/\D/','',$_POST['cpf']??'');
    $telefone=trim($_POST['telefone']??''); $email=trim($_POST['email']??'');
    $senha=$_POST['senha']??''; $confirmar=$_POST['confirmar_senha']??'';
    if($nome===''||$telefone===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($cpf)!==11) $erro='Preencha nome, telefone, e-mail válido e CPF com 11 números.';
    elseif(strlen($senha)<8) $erro='A senha deve ter pelo menos 8 caracteres.';
    elseif($senha!==$confirmar) $erro='As senhas não coincidem.';
    else {
        $stmt=$conexao->prepare('SELECT id_usuario FROM usuarios WHERE email=?'); $stmt->bind_param('s',$email); $stmt->execute();
        if($stmt->get_result()->fetch_assoc()) $erro='Este e-mail já possui cadastro.';
        else {
            try {
                $conexao->begin_transaction();
                $cpfCifrado=criptografar_cpf($cpf);
                $stmt=$conexao->prepare('INSERT INTO tutores (nome,cpf_criptografado,telefone,email) VALUES (?,?,?,?)');
                $stmt->bind_param('ssss',$nome,$cpfCifrado,$telefone,$email); $stmt->execute();
                $idTutor=$conexao->insert_id; $hash=password_hash($senha,PASSWORD_DEFAULT);
                $stmt=$conexao->prepare("INSERT INTO usuarios (nome,email,senha,tipo,id_tutor) VALUES (?,?,?,'usuario',?)");
                $stmt->bind_param('sssi',$nome,$email,$hash,$idTutor); $stmt->execute();
                $conexao->commit();
                header('Location: login.php?cadastro=sucesso'); exit;
            } catch(Throwable $e) { $conexao->rollback(); $erro='Não foi possível concluir o cadastro. Tente novamente.'; }
        }
    }
}
?>
<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Cadastro de cliente | VetCare</title><link rel="stylesheet" href="assets/style.css"></head>
<body class="login-page"><section class="login-card"><div class="logo-mark">＋</div><p class="eyebrow">ÁREA DO CLIENTE</p><h1>Crie sua conta</h1><p class="muted">Cadastre-se para registrar seus pets e solicitar consultas.</p>
<?php if($erro):?><div class="alert error"><?=h($erro)?></div><?php endif;?>
<form method="post"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><label>Nome completo</label><input name="nome" required><label>CPF</label><input name="cpf" inputmode="numeric" maxlength="14" required><label>Telefone</label><input name="telefone" required><label>E-mail</label><input type="email" name="email" required><label>Senha (mínimo 8 caracteres)</label><input type="password" name="senha" required minlength="8"><label>Confirme a senha</label><input type="password" name="confirmar_senha" required minlength="8"><button class="btn full">Criar minha conta</button></form><p class="hint"><a href="login.php">Já tenho cadastro · Entrar</a></p></section></body></html>
