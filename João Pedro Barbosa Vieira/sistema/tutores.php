<?php
require_once __DIR__ . '/funcoes.php';
exigir_admin();
validar_csrf();

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'excluir') {
        $id = (int)($_POST['id_tutor'] ?? 0);
        if ($id <= 0) {
            $erro = 'Tutor inválido.';
        } else {
            // Não apaga tutor que ainda tenha atendimentos em andamento.
            $stmt = $conexao->prepare("SELECT COUNT(*) total FROM agendamentos a JOIN pets p ON p.id_pet=a.id_pet WHERE p.id_tutor=? AND a.status NOT IN ('Concluído','Cancelado')");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $temAtivos = (int)$stmt->get_result()->fetch_assoc()['total'];

            if ($temAtivos > 0) {
                $erro = 'Este tutor possui agendamentos ativos. Conclua ou cancele esses atendimentos antes de excluir o tutor.';
            } else {
                try {
                    $conexao->begin_transaction();
                    $stmt = $conexao->prepare('DELETE a FROM agendamentos a JOIN pets p ON p.id_pet=a.id_pet WHERE p.id_tutor=?');
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $stmt = $conexao->prepare('DELETE FROM pets WHERE id_tutor=?');
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $stmt = $conexao->prepare('DELETE FROM tutores WHERE id_tutor=?');
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $excluido = $stmt->affected_rows;
                    $conexao->commit();
                    $mensagem = $excluido ? 'Tutor, pets vinculados e registros de atendimentos concluídos/cancelados foram excluídos.' : 'Tutor não encontrado.';
                } catch (Throwable $e) {
                    $conexao->rollback();
                    $erro = 'Não foi possível excluir o tutor. Verifique os vínculos e tente novamente.';
                }
            }
        }
    }

    if ($acao === 'salvar') {
        $nome = trim($_POST['nome'] ?? '');
        $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');

        if ($nome === '' || $telefone === '') {
            $erro = 'Nome e telefone são obrigatórios.';
        } elseif ($cpf !== '' && strlen($cpf) !== 11) {
            $erro = 'O CPF deve conter 11 números.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Informe um e-mail válido.';
        } else {
            $cpfCifrado = $cpf !== '' ? criptografar_cpf($cpf) : null;
            if (!empty($_POST['id_tutor'])) {
                $id = (int)$_POST['id_tutor'];
                $stmt = $conexao->prepare('UPDATE tutores SET nome=?, cpf_criptografado=COALESCE(?,cpf_criptografado), telefone=?, email=?, endereco=? WHERE id_tutor=?');
                $stmt->bind_param('sssssi', $nome, $cpfCifrado, $telefone, $email, $endereco, $id);
                $stmt->execute();
                $mensagem = 'Dados do tutor atualizados.';
            } else {
                $stmt = $conexao->prepare('INSERT INTO tutores (nome,cpf_criptografado,telefone,email,endereco) VALUES (?,?,?,?,?)');
                $stmt->bind_param('sssss', $nome, $cpfCifrado, $telefone, $email, $endereco);
                $stmt->execute();
                $mensagem = 'Tutor cadastrado com sucesso.';
            }
        }
    }
}

$edicao = null;
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $stmt = $conexao->prepare('SELECT * FROM tutores WHERE id_tutor=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edicao = $stmt->get_result()->fetch_assoc();
}

$busca = trim($_GET['busca'] ?? '');
if ($busca !== '') {
    $termo = '%' . $busca . '%';
    $stmt = $conexao->prepare('SELECT * FROM tutores WHERE nome LIKE ? OR telefone LIKE ? OR email LIKE ? ORDER BY nome');
    $stmt->bind_param('sss', $termo, $termo, $termo);
    $stmt->execute();
    $lista = $stmt->get_result();
} else {
    $lista = $conexao->query('SELECT * FROM tutores ORDER BY nome');
}

layout_inicio('Tutores');
?>
<div class="page-heading"><div><p class="eyebrow">CADASTROS</p><h1>Gerenciamento de tutores</h1><p class="muted">Cadastre, pesquise, atualize e exclua responsáveis pelos pets.</p></div></div>
<?php if ($mensagem): ?><div class="alert success"><?=h($mensagem)?></div><?php endif; ?>
<?php if ($erro): ?><div class="alert error"><?=h($erro)?></div><?php endif; ?>

<div class="panel">
    <h2><?= $edicao ? 'Editar tutor' : 'Novo tutor' ?></h2>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id_tutor" value="<?=h((string)($edicao['id_tutor'] ?? ''))?>">
        <div><label>Nome completo *</label><input name="nome" required value="<?=h($edicao['nome'] ?? '')?>"></div>
        <div><label>CPF (11 números)</label><input name="cpf" inputmode="numeric" maxlength="14" placeholder="Somente ao cadastrar/alterar"></div>
        <div><label>Telefone *</label><input name="telefone" required value="<?=h($edicao['telefone'] ?? '')?>"></div>
        <div><label>E-mail</label><input type="email" name="email" value="<?=h($edicao['email'] ?? '')?>"></div>
        <div class="span-2"><label>Endereço</label><input name="endereco" value="<?=h($edicao['endereco'] ?? '')?>"></div>
        <div class="span-2 actions">
            <button class="btn" type="submit"><?= $edicao ? 'Salvar alterações' : 'Cadastrar tutor' ?></button>
            <?php if ($edicao): ?><a class="btn secondary" href="tutores.php">Cancelar</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="panel">
    <div class="panel-title">
        <h2>Tutores cadastrados</h2>
        <form method="get" class="search"><input name="busca" placeholder="Buscar por nome, telefone ou e-mail" value="<?=h($busca)?>"><button class="btn small">Buscar</button></form>
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th>Nome</th><th>Telefone</th><th>E-mail</th><th>Endereço</th><th>Ações</th></tr></thead>
        <tbody>
        <?php if ($lista->num_rows === 0): ?>
            <tr><td colspan="5">Nenhum tutor encontrado.</td></tr>
        <?php else: while ($t = $lista->fetch_assoc()): ?>
            <tr>
                <td><?=h($t['nome'])?></td><td><?=h($t['telefone'])?></td><td><?=h($t['email'])?></td><td><?=h($t['endereco'])?></td>
                <td class="action-cell">
                    <a class="text-link" href="tutores.php?editar=<?=(int)$t['id_tutor']?>">Editar</a>
                    <form method="post" class="delete-form" onsubmit="return confirm('Tem certeza de que deseja excluir este tutor?');">
                        <input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id_tutor" value="<?=(int)$t['id_tutor']?>">
                        <button class="btn danger small" type="submit">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table></div>
</div>
<?php layout_fim(); ?>
