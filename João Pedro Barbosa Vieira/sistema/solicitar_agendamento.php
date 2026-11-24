<?php
require_once __DIR__ . '/funcoes.php';
exigir_cliente(); validar_csrf();
$idTutor=(int)($_SESSION['usuario_tutor_id']??0);$erro='';$mensagem='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $idPet=(int)($_POST['id_pet']??0);$data=$_POST['data_agendamento']??'';$horario=$_POST['horario']??'';$motivo=trim($_POST['motivo']??'');
    $stmt=$conexao->prepare('SELECT id_pet FROM pets WHERE id_pet=? AND id_tutor=?');$stmt->bind_param('ii',$idPet,$idTutor);$stmt->execute();$petValido=$stmt->get_result()->fetch_assoc();
    if(!$petValido||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$data)||!preg_match('/^\d{2}:\d{2}$/',$horario)||$motivo==='')$erro='Confira o pet, a data, o horário e o motivo.';
    elseif($data<date('Y-m-d'))$erro='A data solicitada não pode estar no passado.';
    else{$stmt=$conexao->prepare("INSERT INTO agendamentos (id_pet,data_agendamento,horario,motivo,status) VALUES (?,?,?,?, 'Solicitado')");$stmt->bind_param('isss',$idPet,$data,$horario,$motivo);$stmt->execute();$mensagem='Solicitação enviada! A clínica ainda precisa confirmar o agendamento.';}
}
$stmt=$conexao->prepare('SELECT id_pet,nome,especie FROM pets WHERE id_tutor=? ORDER BY nome');$stmt->bind_param('i',$idTutor);$stmt->execute();$pets=$stmt->get_result();
layout_inicio('Solicitar agendamento');
?>
<div class="page-heading"><div><p class="eyebrow">ÁREA DO CLIENTE</p><h1>Solicitar agendamento</h1><p class="muted">Escolha um pet e informe a data e o horário desejados.</p></div></div>
<?php if($mensagem):?><div class="alert success"><?=h($mensagem)?></div><?php endif;?><?php if($erro):?><div class="alert error"><?=h($erro)?></div><?php endif;?>
<div class="panel"><h2>Nova solicitação</h2><?php if(!$pets->num_rows):?><p>Antes de solicitar uma consulta, <a class="text-link" href="meus_pets.php">cadastre seu primeiro pet</a>.</p><?php else:?><form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><div><label>Pet *</label><select name="id_pet" required><option value="">Selecione seu pet</option><?php while($p=$pets->fetch_assoc()):?><option value="<?=(int)$p['id_pet']?>"><?=h($p['nome'].' - '.$p['especie'])?></option><?php endwhile;?></select></div><div><label>Data desejada *</label><input type="date" name="data_agendamento" min="<?=date('Y-m-d')?>" required></div><div><label>Horário desejado *</label><input type="time" name="horario" required></div><div><label>Motivo da consulta *</label><input name="motivo" maxlength="255" required placeholder="Ex.: vacinação, consulta de rotina"></div><div class="span-2"><button class="btn">Enviar solicitação</button></div></form><?php endif;?></div>
<?php layout_fim();?>
