<?php
require_once __DIR__ . '/funcoes.php';
exigir_admin();
$totalTutores = (int)$conexao->query('SELECT COUNT(*) total FROM tutores')->fetch_assoc()['total'];
$totalPets = (int)$conexao->query('SELECT COUNT(*) total FROM pets')->fetch_assoc()['total'];
$totalAgendamentos = (int)$conexao->query("SELECT COUNT(*) total FROM agendamentos WHERE data_agendamento >= CURDATE() AND status <> 'Cancelado'")->fetch_assoc()['total'];
$proximos = $conexao->query("SELECT a.data_agendamento, a.horario, a.motivo, a.status, p.nome pet, t.nome tutor FROM agendamentos a JOIN pets p ON p.id_pet=a.id_pet JOIN tutores t ON t.id_tutor=p.id_tutor WHERE a.data_agendamento >= CURDATE() AND a.status <> 'Cancelado' ORDER BY a.data_agendamento, a.horario LIMIT 5");
layout_inicio('Visão geral');
?>
<div class="page-heading"><div><p class="eyebrow">PAINEL PRINCIPAL</p><h1>Visão geral</h1><p class="muted">Acompanhe a rotina da clínica em um só lugar.</p></div><span class="date-pill"><?= date('d/m/Y') ?></span></div>
<div class="stats"><article class="stat"><span>Tutores cadastrados</span><strong><?= $totalTutores ?></strong></article><article class="stat"><span>Pets cadastrados</span><strong><?= $totalPets ?></strong></article><article class="stat"><span>Próximos atendimentos</span><strong><?= $totalAgendamentos ?></strong></article></div>
<div class="panel"><div class="panel-title"><div><h2>Próximos agendamentos</h2><p class="muted">Consultas futuras organizadas por data e horário.</p></div><a class="btn small" href="agendamentos.php">Ver todos</a></div>
<div class="table-wrap"><table><thead><tr><th>Data</th><th>Horário</th><th>Pet</th><th>Tutor</th><th>Motivo</th><th>Status</th></tr></thead><tbody>
<?php if ($proximos->num_rows === 0): ?><tr><td colspan="6">Nenhum agendamento futuro.</td></tr><?php else: while ($a=$proximos->fetch_assoc()): ?><tr><td><?= date('d/m/Y', strtotime($a['data_agendamento'])) ?></td><td><?= h(substr($a['horario'],0,5)) ?></td><td><?= h($a['pet']) ?></td><td><?= h($a['tutor']) ?></td><td><?= h($a['motivo']) ?></td><td><span class="status"><?= h($a['status']) ?></span></td></tr><?php endwhile; endif; ?>
</tbody></table></div></div>
<?php layout_fim(); ?>
