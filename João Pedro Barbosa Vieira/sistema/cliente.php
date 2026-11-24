<?php
require_once __DIR__ . '/funcoes.php';
exigir_cliente();
$idTutor=(int)($_SESSION['usuario_tutor_id']??0);
$stmt=$conexao->prepare('SELECT COUNT(*) total FROM pets WHERE id_tutor=?');$stmt->bind_param('i',$idTutor);$stmt->execute();$totalPets=(int)$stmt->get_result()->fetch_assoc()['total'];
$stmt=$conexao->prepare("SELECT COUNT(*) total FROM agendamentos a JOIN pets p ON p.id_pet=a.id_pet WHERE p.id_tutor=? AND a.status NOT IN ('Cancelado','Concluído')");$stmt->bind_param('i',$idTutor);$stmt->execute();$totalAg=(int)$stmt->get_result()->fetch_assoc()['total'];
$stmt=$conexao->prepare("SELECT a.data_agendamento,a.horario,a.motivo,a.status,p.nome pet FROM agendamentos a JOIN pets p ON p.id_pet=a.id_pet WHERE p.id_tutor=? AND a.status <> 'Concluído' ORDER BY a.data_agendamento DESC,a.horario DESC LIMIT 8");$stmt->bind_param('i',$idTutor);$stmt->execute();$lista=$stmt->get_result();
layout_inicio('Meu painel');
?>
<div class="page-heading"><div><p class="eyebrow">ÁREA DO CLIENTE</p><h1>Olá, <?=h($_SESSION['usuario_nome'])?>!</h1><p class="muted">Gerencie seus animais e acompanhe suas solicitações.</p></div></div>
<div class="stats"><article class="stat"><span>Meus pets</span><strong><?=$totalPets?></strong></article><article class="stat"><span>Consultas e solicitações</span><strong><?=$totalAg?></strong></article><article class="stat"><span>Área do cliente</span><strong>Ativa</strong></article></div>
<div class="quick-actions"><a class="btn" href="meus_pets.php">Cadastrar / ver meus pets</a><a class="btn secondary" href="solicitar_agendamento.php">Solicitar agendamento</a></div>
<div class="panel"><h2>Minhas solicitações</h2><div class="table-wrap"><table><thead><tr><th>Data</th><th>Horário</th><th>Pet</th><th>Motivo</th><th>Situação</th></tr></thead><tbody><?php if(!$lista->num_rows):?><tr><td colspan="5">Você ainda não possui solicitações.</td></tr><?php else:while($a=$lista->fetch_assoc()):?><tr><td><?=date('d/m/Y',strtotime($a['data_agendamento']))?></td><td><?=h(substr($a['horario'],0,5))?></td><td><?=h($a['pet'])?></td><td><?=h($a['motivo'])?></td><td><span class="status"><?=h($a['status'])?></span></td></tr><?php endwhile;endif;?></tbody></table></div></div>
<?php layout_fim();?>
