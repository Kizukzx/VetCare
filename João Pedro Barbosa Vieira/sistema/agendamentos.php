<?php
require_once __DIR__ . '/funcoes.php';
exigir_admin();
validar_csrf();

$mensagem = '';
$erro = '';
$permitidos = ['Solicitado', 'Agendado', 'Confirmado', 'Concluído', 'Cancelado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar') {
        $idPet = (int)($_POST['id_pet'] ?? 0);
        $data = $_POST['data_agendamento'] ?? '';
        $horario = $_POST['horario'] ?? '';
        $motivo = trim($_POST['motivo'] ?? '');
        $status = $_POST['status'] ?? 'Agendado';

        $dataValida = preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)
            && checkdate((int)substr($data, 5, 2), (int)substr($data, 8, 2), (int)substr($data, 0, 4));
        $horaValida = preg_match('/^\d{2}:\d{2}$/', $horario)
            && (int)substr($horario, 0, 2) <= 23
            && (int)substr($horario, 3, 2) <= 59;

        $stmt = $conexao->prepare('SELECT id_pet FROM pets WHERE id_pet = ?');
        $stmt->bind_param('i', $idPet);
        $stmt->execute();
        $petExiste = $stmt->get_result()->fetch_assoc();

        if (!$petExiste || !$dataValida || !$horaValida || $motivo === '' || !in_array($status, ['Agendado', 'Confirmado'], true)) {
            $erro = 'Confira o pet, a data, o horário, o motivo e o status informados.';
        } else {
            $stmt = $conexao->prepare('INSERT INTO agendamentos (id_pet, data_agendamento, horario, motivo, status) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('issss', $idPet, $data, $horario, $motivo, $status);
            if ($stmt->execute()) {
                $mensagem = 'Consulta agendada com sucesso pelo administrador.';
            } else {
                $erro = 'Não foi possível cadastrar o agendamento. Tente novamente.';
            }
        }
    }

    if ($acao === 'atualizar') {
        $id = (int)($_POST['id_agendamento'] ?? 0);
        $data = $_POST['data_agendamento'] ?? '';
        $horario = $_POST['horario'] ?? '';
        $status = $_POST['status'] ?? 'Agendado';

        $dataValida = preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)
            && checkdate((int)substr($data, 5, 2), (int)substr($data, 8, 2), (int)substr($data, 0, 4));
        $horaValida = preg_match('/^\d{2}:\d{2}$/', $horario)
            && (int)substr($horario, 0, 2) <= 23
            && (int)substr($horario, 3, 2) <= 59;

        if (!$id || !$dataValida || !$horaValida || !in_array($status, $permitidos, true)) {
            $erro = 'Confira os dados informados.';
        } else {
            $stmt = $conexao->prepare('SELECT status FROM agendamentos WHERE id_agendamento = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $atual = $stmt->get_result()->fetch_assoc();

            if (!$atual) {
                $erro = 'Agendamento não encontrado.';
            } elseif ($atual['status'] === 'Concluído') {
                $erro = 'Este agendamento já foi concluído e não pode mais ser alterado.';
            } else {
                $stmt = $conexao->prepare("UPDATE agendamentos SET data_agendamento = ?, horario = ?, status = ? WHERE id_agendamento = ? AND status <> 'Concluído'");
                $stmt->bind_param('sssi', $data, $horario, $status, $id);
                $stmt->execute();
                if ($status === 'Concluído') {
                    $mensagem = 'Atendimento concluído. Ele foi retirado da agenda ativa e não poderá mais ser alterado.';
                } else {
                    $mensagem = 'Agendamento atualizado.';
                }
            }
        }
    }
}

$petsSelect = $conexao->query("SELECT p.id_pet, p.nome pet, t.nome tutor
    FROM pets p JOIN tutores t ON t.id_tutor = p.id_tutor
    ORDER BY t.nome, p.nome");

$lista = $conexao->query("SELECT a.id_agendamento, a.data_agendamento, a.horario, a.motivo, a.status, p.nome pet, t.nome tutor, t.telefone, t.email
    FROM agendamentos a
    JOIN pets p ON p.id_pet = a.id_pet
    JOIN tutores t ON t.id_tutor = p.id_tutor
    WHERE a.status <> 'Concluído'
    ORDER BY a.data_agendamento, a.horario");

layout_inicio('Agendamentos');
?>
<div class="page-heading"><div><p class="eyebrow">ATENDIMENTO</p><h1>Agendamentos</h1><p class="muted">O administrador pode cadastrar consultas e gerenciar os atendimentos.</p></div></div>
<?php if ($mensagem): ?><div class="alert success"><?=h($mensagem)?></div><?php endif; ?>
<?php if ($erro): ?><div class="alert error"><?=h($erro)?></div><?php endif; ?>

<div class="panel">
    <h2>Agendar nova consulta</h2>
    <?php if ($petsSelect->num_rows === 0): ?>
        <p class="muted">Cadastre um tutor e um pet antes de agendar uma consulta.</p>
    <?php else: ?>
        <form method="post" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>">
            <input type="hidden" name="acao" value="criar">
            <div><label>Pet e tutor responsável *</label>
                <select name="id_pet" required>
                    <option value="">Selecione um pet</option>
                    <?php while ($petOpcao = $petsSelect->fetch_assoc()): ?>
                        <option value="<?=(int)$petOpcao['id_pet']?>"><?=h($petOpcao['pet'] . ' — ' . $petOpcao['tutor'])?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div><label>Motivo da consulta *</label><input name="motivo" maxlength="255" required placeholder="Ex.: Consulta de rotina"></div>
            <div><label>Data *</label><input type="date" name="data_agendamento" required min="<?=date('Y-m-d')?>"></div>
            <div><label>Horário *</label><input type="time" name="horario" required></div>
            <div><label>Status inicial *</label>
                <select name="status" required><option value="Agendado" selected>Agendado</option><option value="Confirmado">Confirmado</option></select>
            </div>
            <div class="span-2 actions"><button class="btn" type="submit">Agendar consulta</button></div>
        </form>
    <?php endif; ?>
</div>

<div class="panel">
    <h2>Agenda ativa da clínica</h2>
    <div class="table-wrap"><table>
    <thead><tr><th>Data e horário</th><th>Pet</th><th>Tutor e contato</th><th>Motivo</th><th>Status</th><th>Atualizar</th></tr></thead><tbody>
    <?php if ($lista->num_rows === 0): ?><tr><td colspan="6">Nenhum agendamento ativo. Os concluídos ficam fora desta agenda.</td></tr>
    <?php else: while ($a = $lista->fetch_assoc()): ?>
    <tr>
    <td><?=date('d/m/Y', strtotime($a['data_agendamento']))?><br><?=h(substr($a['horario'], 0, 5))?></td>
    <td><?=h($a['pet'])?></td>
    <td><?=h($a['tutor'])?><br><small><?=h($a['telefone'])?> · <?=h($a['email'])?></small></td>
    <td><?=h($a['motivo'])?></td>
    <td><span class="status"><?=h($a['status'])?></span></td>
    <td><form method="post" class="inline-form">
    <input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>">
    <input type="hidden" name="acao" value="atualizar">
    <input type="hidden" name="id_agendamento" value="<?=(int)$a['id_agendamento']?>">
    <input type="date" name="data_agendamento" required value="<?=h($a['data_agendamento'])?>">
    <input type="time" name="horario" required value="<?=h(substr($a['horario'], 0, 5))?>">
    <select name="status"><?php foreach ($permitidos as $s): ?><option <?= $a['status'] === $s ? 'selected' : '' ?>><?=h($s)?></option><?php endforeach; ?></select>
    <button class="btn small" type="submit">Salvar</button>
    </form></td>
    </tr>
    <?php endwhile; endif; ?>
    </tbody></table></div>
</div>
<?php layout_fim(); ?>
