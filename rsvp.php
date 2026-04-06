<?php
require_once 'config.php';

$pdo = getDB();
$id_evento = (int)($_GET['id'] ?? 0);
$success = false;
$error = '';

if ($id_evento > 0) {
    $stmt = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
    $stmt->execute([$id_evento]);
    $evento = $stmt->fetch();

    if (!$evento) {
        die("Evento não encontrado.");
    }
} else {
    die("ID do evento inválido.");
}

// Processar RSVP Externo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = strip_tags(trim($_POST['nome']));
    $whatsapp = strip_tags(trim($_POST['whatsapp']));
    $acompanhantes = (int)($_POST['acompanhantes'] ?? 0);
    $contribuicao = strip_tags(trim($_POST['contribuicao'] ?? ''));

    if (!empty($nome)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO eventos_presenca_externa (evento_id, nome, whatsapp, acompanhantes, contribuicao_item) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id_evento, $nome, $whatsapp, $acompanhantes, $contribuicao]);
            $success = true;
        } catch (Exception $e) {
            $error = "Erro ao salvar: " . $e->getMessage();
        }
    } else {
        $error = "Por favor, preencha seu nome.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Presença - <?php echo htmlspecialchars($evento['titulo']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6e2b3a;
            --secondary: #9d405a;
            --bg-body: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --white: #ffffff;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); color: var(--text-main); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .container { background: var(--white); width: 100%; max-width: 500px; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.1); border: 1px solid var(--border); }
        
        .header { background: linear-gradient(135deg, var(--primary), var(--secondary)); padding: 40px 30px; color: white; text-align: center; position: relative; }
        .header h1 { font-size: 1.8em; font-weight: 800; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 0.95em; }

        .body { padding: 35px 30px; }
        .event-info { margin-bottom: 30px; background: #f1f5f9; padding: 20px; border-radius: 16px; font-size: 0.9em; line-height: 1.6; }
        .event-info div { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; color: var(--text-muted); }
        .event-info div i { color: var(--primary); width: 16px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9em; color: var(--text-main); }
        .form-control { width: 100%; padding: 14px 18px; border-radius: 12px; border: 2px solid var(--border); font-family: inherit; transition: 0.3s; font-size: 1em; }
        .form-control:focus { outline: none; border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px rgba(110,43,58,0.1); }
        
        .btn { width: 100%; padding: 16px; border-radius: 14px; border: none; background: var(--primary); color: white; font-weight: 800; font-size: 1em; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .btn:hover { background: #5a2330; transform: translateY(-2px); box-shadow: 0 8px 15px rgba(110,43,58,0.2); }
        
        .success-msg { text-align: center; padding: 40px 20px; }
        .success-msg i { font-size: 4em; color: #10b981; margin-bottom: 20px; display: block; }
        .success-msg h2 { margin-bottom: 10px; color: var(--text-main); }
        .success-msg p { color: var(--text-muted); }

        .capa { width: 100%; height: 200px; object-fit: cover; }
    </style>
</head>
<body>
    <div class="container">
        <?php if($success): ?>
            <div class="success-msg">
                <i class="fa-solid fa-circle-check"></i>
                <h2>Presença Confirmada!</h2>
                <p>Obrigado, <?php echo htmlspecialchars($nome); ?>. Sua participação foi registrada com sucesso.</p>
                <button onclick="window.location.reload()" class="btn" style="margin-top:25px; background: #f1f5f9; color: var(--text-main);">Enviar outra resposta</button>
            </div>
        <?php else: ?>
            <div class="header">
                <?php if(!empty($evento['capa']) && file_exists($evento['capa'])): ?>
                    <img src="<?php echo htmlspecialchars($evento['capa']); ?>" class="capa" style="position: absolute; inset: 0; opacity: 0.3; filter: blur(2px);">
                <?php endif; ?>
                <div style="position: relative;">
                    <p style="text-transform: uppercase; letter-spacing: 2px; font-weight: 800; font-size: 0.7em; margin-bottom: 10px; opacity: 0.8;">Confirmação de Presença</p>
                    <h1><?php echo htmlspecialchars($evento['titulo']); ?></h1>
                </div>
            </div>
            <div class="body">
                <div class="event-info">
                    <div><i class="fa-regular fa-calendar"></i> <?php echo date('d/m/Y \à\s H:i', strtotime($evento['data_evento'])); ?></div>
                    <div><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($evento['local']); ?></div>
                    <?php if(!empty($evento['descricao'])): ?>
                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-style: italic;"><?php echo nl2br(htmlspecialchars($evento['descricao'])); ?></div>
                    <?php endif; ?>
                </div>

                <?php if($error): ?>
                    <div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9em; font-weight: 600;">
                        <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Seu Nome Completo *</label>
                        <input type="text" name="nome" class="form-control" placeholder="Como devemos te chamar?" required>
                    </div>
                    
                    <div class="form-group">
                        <label>WhatsApp / Telefone</label>
                        <input type="tel" name="whatsapp" class="form-control" placeholder="(00) 00000-0000">
                    </div>

                    <?php if($evento['permite_acompanhantes']): ?>
                        <div class="form-group">
                            <label>Vai levar acompanhantes? (Quantidade)</label>
                            <input type="number" name="acompanhantes" class="form-control" value="0" min="0">
                        </div>
                    <?php endif; ?>

                    <?php if($evento['colaborativo_ativo']): 
                        $itens = array_filter(array_map('trim', explode(',', $evento['itens_colaborativos'])));
                        if(!empty($itens)):
                    ?>
                        <div class="form-group">
                            <label>Deseja contribuir com algum item?</label>
                            <select name="contribuicao" class="form-control">
                                <option value="">Não irei levar nada / Outro</option>
                                <?php foreach($itens as $it): ?>
                                    <option value="<?php echo htmlspecialchars($it); ?>"><?php echo htmlspecialchars($it); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; endif; ?>

                    <button type="submit" class="btn">
                        <i class="fa-solid fa-paper-plane"></i> Confirmar Presença
                    </button>
                    
                    <p style="text-align: center; margin-top: 25px; font-size: 0.75em; color: var(--text-muted);">
                        Sua segurança é nossa prioridade. Dados usados apenas para gestão do evento.
                    </p>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
