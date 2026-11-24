<?php
require_once __DIR__ . '/funcoes.php';
exigir_admin();
validar_csrf();

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'excluir') {
        $id = (int)($_POST['id_pet'] ?? 0);
        if ($id <= 0) {
            $erro = 'Pet inválido.';
        } else {
            $stmt = $conexao->prepare("SELECT COUNT(*) total FROM agendamentos WHERE id_pet=? AND status NOT IN ('Concluído','Cancelado')");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $temAtivos = (int)$stmt->get_result()->fetch_assoc()['total'];

            if ($temAtivos > 0) {
                $erro = 'Este pet possui agendamentos ativos. Conclua ou cancele os atendimentos antes de excluir o pet.';
            } else {
                try {
                    $conexao->begin_transaction();
                    $stmt = $conexao->prepare('DELETE FROM agendamentos WHERE id_pet=?');
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $stmt = $conexao->prepare('DELETE FROM pets WHERE id_pet=?');
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $excluido = $stmt->affected_rows;
                    $conexao->commit();
                    $mensagem = $excluido ? 'Pet e seus registros de atendimentos concluídos/cancelados foram excluídos.' : 'Pet não encontrado.';
                } catch (Throwable $e) {
                    $conexao->rollback();
                    $erro = 'Não foi possível excluir o pet. Verifique os vínculos e tente novamente.';
                }
            }
        }
    }

    if ($acao === 'salvar') {
        $idTutor = (int)($_POST['id_tutor'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $especie = trim($_POST['especie'] ?? '');
        $raca = trim($_POST['raca'] ?? '');
        $idade = ($_POST['idade'] ?? '') !== '' ? (int)$_POST['idade'] : null;
        $obs = trim($_POST['observacoes'] ?? '');
        $idPet = (int)($_POST['id_pet'] ?? 0);

        $check = $conexao->prepare('SELECT id_tutor FROM tutores WHERE id_tutor=?');
        $check->bind_param('i', $idTutor);
        $check->execute();

        if ($nome === '' || $especie === '' || !$check->get_result()->fetch_assoc()) {
            $erro = 'Informe o nome, a espécie e selecione um tutor válido.';
        } elseif ($idade !== null && $idade < 0) {
            $erro = 'A idade não pode ser negativa.';
        } else {
            if ($idPet > 0) {
                $stmt = $conexao->prepare('UPDATE pets SET id_tutor=?, nome=?, especie=?, raca=?, idade=?, observacoes=? WHERE id_pet=?');
                $stmt->bind_param('isssisi', $idTutor, $nome, $especie, $raca, $idade, $obs, $idPet);
                $stmt->execute();
                $mensagem = 'Dados do pet atualizados.';
            } else {
                $stmt = $conexao->prepare('INSERT INTO pets (id_tutor,nome,especie,raca,idade,observacoes) VALUES (?,?,?,?,?,?)');
                $stmt->bind_param('isssis', $idTutor, $nome, $especie, $raca, $idade, $obs);
                $stmt->execute();
                $mensagem = 'Pet cadastrado com sucesso.';
            }
        }
    }
}

$edicao = null;
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $stmt = $conexao->prepare('SELECT * FROM pets WHERE id_pet=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edicao = $stmt->get_result()->fetch_assoc();
}

$tutores = $conexao->query('SELECT id_tutor,nome FROM tutores ORDER BY nome');
$pets = $conexao->query('SELECT p.*,t.nome tutor FROM pets p JOIN tutores t ON t.id_tutor=p.id_tutor ORDER BY p.nome');
layout_inicio('Pets');
?>
<div class="page-heading"><div><p class="eyebrow">CADASTROS</p><h1>Gerenciamento de pets</h1><p class="muted">Todo pet deve estar vinculado a um tutor cadastrado.</p></div></div>
<?php if ($mensagem): ?><div class="alert success"><?=h($mensagem)?></div><?php endif; ?>
<?php if ($erro): ?><div class="alert error"><?=h($erro)?></div><?php endif; ?>

<div class="panel">
    <h2><?= $edicao ? 'Editar pet' : 'Cadastrar novo pet' ?></h2>
    <?php if ($tutores->num_rows === 0): ?>
        <p>Cadastre um tutor antes de cadastrar um pet.</p>
    <?php else: ?>
        <form method="post" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>">
            <input type="hidden" name="acao" value="salvar">
            <input type="hidden" name="id_pet" value="<?=h((string)($edicao['id_pet'] ?? ''))?>">
            <div><label>Nome *</label><input name="nome" required value="<?=h($edicao['nome'] ?? '')?>"></div>
            <div><label>Tutor responsável *</label><select name="id_tutor" required><option value="">Selecione</option>
                <?php while ($t = $tutores->fetch_assoc()): ?>
                    <option value="<?=(int)$t['id_tutor']?>" <?=isset($edicao['id_tutor']) && (int)$edicao['id_tutor']===(int)$t['id_tutor']?'selected':''?>><?=h($t['nome'])?></option>
                <?php endwhile; ?>
            </select></div>
            <div><label>Espécie *</label><select name="especie" required><option value="">Selecione</option>
                <?php foreach (['Cachorro','Gato','Ave','Outro'] as $e): ?><option <?=($edicao['especie'] ?? '')===$e?'selected':''?>><?=h($e)?></option><?php endforeach; ?>
            </select></div>
            <div><label>Raça</label><input name="raca" value="<?=h($edicao['raca'] ?? '')?>"></div>
            <div><label>Idade (anos)</label><input type="number" name="idade" min="0" value="<?=h((string)($edicao['idade'] ?? ''))?>"></div>
            <div><label>Observações</label><input name="observacoes" value="<?=h($edicao['observacoes'] ?? '')?>"></div>
            <div class="span-2 actions">
                <button class="btn" type="submit"><?= $edicao ? 'Salvar alterações' : 'Cadastrar pet' ?></button>
                <?php if ($edicao): ?><a class="btn secondary" href="pets.php">Cancelar</a><?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</div>

<div class="panel">
    <h2>Pets cadastrados</h2>
    <div class="table-wrap"><table>
        <thead><tr><th>Pet</th><th>Espécie</th><th>Raça</th><th>Idade</th><th>Tutor responsável</th><th>Ações</th></tr></thead>
        <tbody>
        <?php if ($pets->num_rows === 0): ?>
            <tr><td colspan="6">Nenhum pet cadastrado.</td></tr>
        <?php else: while ($p = $pets->fetch_assoc()): ?>
            <tr>
                <td><?=h($p['nome'])?></td><td><?=h($p['especie'])?></td><td><?=h($p['raca'])?></td><td><?=h((string)$p['idade'])?></td><td><?=h($p['tutor'])?></td>
                <td class="action-cell">
                    <a class="text-link" href="pets.php?editar=<?=(int)$p['id_pet']?>">Editar</a>
                    <form method="post" class="delete-form" onsubmit="return confirm('Tem certeza de que deseja excluir este pet?');">
                        <input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id_pet" value="<?=(int)$p['id_pet']?>">
                        <button class="btn danger small" type="submit">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table></div>
</div>
<?php layout_fim(); ?>
