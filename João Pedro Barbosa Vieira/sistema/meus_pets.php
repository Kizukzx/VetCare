<?php
require_once __DIR__ . '/funcoes.php';
exigir_cliente(); validar_csrf();
$idTutor=(int)($_SESSION['usuario_tutor_id']??0);$erro='';$mensagem='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nome=trim($_POST['nome']??'');$especie=trim($_POST['especie']??'');$raca=trim($_POST['raca']??'');$idade=($_POST['idade']??'')!==''?(int)$_POST['idade']:null;
    if($nome===''||$especie==='')$erro='Informe o nome e a espécie do pet.';
    elseif($idade!==null&&$idade<0)$erro='A idade não pode ser negativa.';
    else{$stmt=$conexao->prepare('INSERT INTO pets (id_tutor,nome,especie,raca,idade) VALUES (?,?,?,?,?)');$stmt->bind_param('isssi',$idTutor,$nome,$especie,$raca,$idade);$stmt->execute();$mensagem='Pet cadastrado com sucesso.';}
}
$stmt=$conexao->prepare('SELECT * FROM pets WHERE id_tutor=? ORDER BY nome');$stmt->bind_param('i',$idTutor);$stmt->execute();$pets=$stmt->get_result();
layout_inicio('Meus pets');
?>
<div class="page-heading"><div><p class="eyebrow">ÁREA DO CLIENTE</p><h1>Meus pets</h1><p class="muted">Cadastre os animais que poderão receber atendimento.</p></div></div>
<?php if($mensagem):?><div class="alert success"><?=h($mensagem)?></div><?php endif;?><?php if($erro):?><div class="alert error"><?=h($erro)?></div><?php endif;?>
<div class="panel"><h2>Cadastrar pet</h2><form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><div><label>Nome *</label><input name="nome" required></div><div><label>Espécie *</label><select name="especie" required><option value="">Selecione</option><option>Cachorro</option><option>Gato</option><option>Ave</option><option>Outro</option></select></div><div><label>Raça</label><input name="raca"></div><div><label>Idade (anos)</label><input type="number" min="0" name="idade"></div><div class="span-2"><button class="btn">Salvar pet</button></div></form></div>
<div class="panel"><h2>Animais cadastrados</h2><div class="table-wrap"><table><thead><tr><th>Nome</th><th>Espécie</th><th>Raça</th><th>Idade</th></tr></thead><tbody><?php if(!$pets->num_rows):?><tr><td colspan="4">Você ainda não cadastrou pets.</td></tr><?php else:while($p=$pets->fetch_assoc()):?><tr><td><?=h($p['nome'])?></td><td><?=h($p['especie'])?></td><td><?=h($p['raca'])?></td><td><?=h((string)$p['idade'])?></td></tr><?php endwhile;endif;?></tbody></table></div></div>
<?php layout_fim();?>
