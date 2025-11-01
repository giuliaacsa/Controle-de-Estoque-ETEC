<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $produto_id = $_POST['produto_id'];
        $lote = $_POST['lote'] ?? '';
        $quantidade_pacotes = $_POST['quantidade_pacotes'];
        $data_utilizacao = formatarDataBanco($_POST['data_utilizacao']);
        $responsavel = $_POST['responsavel'];
        $observacoes = $_POST['observacoes'] ?? '';
        
        // Verificar estoque disponível
        $stmt = $db->prepare("SELECT estoque_atual, peso_pacote FROM produtos WHERE id = ?");
        $stmt->execute([$produto_id]);
        $produto = $stmt->fetch();
        
        if (!$produto) {
            throw new Exception("Produto não encontrado!");
        }
        
        if ($produto['estoque_atual'] < $quantidade_pacotes) {
            throw new Exception("Estoque insuficiente! Disponível: {$produto['estoque_atual']} pacotes");
        }
        
        // Calcular peso utilizado
        $peso_utilizado = $quantidade_pacotes * $produto['peso_pacote'];
        
        // Inserir saída
        $stmt = $db->prepare("
            INSERT INTO saidas 
            (produto_id, lote, quantidade_pacotes, peso_utilizado, data_utilizacao, responsavel, observacoes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $produto_id, $lote, $quantidade_pacotes, $peso_utilizado, 
            $data_utilizacao, $responsavel, $observacoes
        ]);
        
        $message = "Saída registrada com sucesso! {$quantidade_pacotes} pacotes removidos do estoque.";
        $messageType = "success";
        
    } catch (Exception $e) {
        $message = "Erro ao registrar saída: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Buscar produtos com estoque
$stmt = $db->query("
    SELECT p.id, p.nome, c.nome as categoria, p.peso_pacote, p.unidade_medida, p.estoque_atual
    FROM produtos p
    INNER JOIN categorias c ON p.categoria_id = c.id
    WHERE p.ativo = TRUE AND p.estoque_atual > 0
    ORDER BY c.nome, p.nome
");
$produtos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Registrar Saída</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="header-card">
                    <h1><i class="bi bi-arrow-up-circle-fill"></i> Registrar Saída</h1>
                    <p class="text-muted mb-0">Registrar utilização de produtos</p>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <i class="bi bi-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card-custom">
                    <h5><i class="bi bi-clipboard-minus"></i> Dados da Saída</h5>
                    
                    <form method="POST" id="formSaida">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="produto_id" class="form-label">
                                    Produto <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="produto_id" name="produto_id" required>
                                    <option value="">Selecione o produto...</option>
                                    <?php 
                                    $currentCategory = '';
                                    foreach ($produtos as $prod): 
                                        if ($currentCategory != $prod['categoria']) {
                                            if ($currentCategory != '') echo '</optgroup>';
                                            echo '<optgroup label="' . htmlspecialchars($prod['categoria']) . '">';
                                            $currentCategory = $prod['categoria'];
                                        }
                                    ?>
                                        <option value="<?php echo $prod['id']; ?>" 
                                                data-peso="<?php echo $prod['peso_pacote']; ?>"
                                                data-unidade="<?php echo $prod['unidade_medida']; ?>"
                                                data-estoque="<?php echo $prod['estoque_atual']; ?>">
                                            <?php echo htmlspecialchars($prod['nome']); ?>
                                            (Estoque: <?php echo $prod['estoque_atual']; ?> pct)
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if ($currentCategory != '') echo '</optgroup>'; ?>
                                </select>
                                <small class="form-text text-muted" id="produtoInfo"></small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="quantidade_pacotes" class="form-label">
                                    Quantidade de Pacotes <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="quantidade_pacotes" 
                                       name="quantidade_pacotes" min="1" step="1" required>
                                <small class="form-text text-muted" id="estoqueAlert"></small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="lote" class="form-label">
                                    Lote (Opcional)
                                </label>
                                <input type="text" class="form-control" id="lote" name="lote">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="alert alert-info" id="calculoInfo" style="display: none;">
                                    <i class="bi bi-calculator"></i>
                                    <strong>Peso a ser utilizado:</strong>
                                    <span id="calculoTexto"></span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="data_utilizacao" class="form-label">
                                    Data de Utilização <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="data_utilizacao" 
                                       name="data_utilizacao" placeholder="dd/mm/aaaa" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="responsavel" class="form-label">
                                    Responsável
                                </label>
                                <input type="text" class="form-control" id="responsavel" 
                                       name="responsavel" placeholder="Nome da merendeira">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="observacoes" class="form-label">
                                    Observações
                                </label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-etec">
                                <i class="bi bi-save"></i> Registrar Saída
                            </button>
                            <a href="historico.php" class="btn btn-outline-etec">
                                <i class="bi bi-clock-history"></i> Ver Histórico
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Máscara de data
        document.getElementById('data_utilizacao').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) value = value.slice(0,2) + '/' + value.slice(2);
            if (value.length >= 5) value = value.slice(0,5) + '/' + value.slice(5,9);
            e.target.value = value;
        });

        // Data atual como padrão
        const hoje = new Date();
        const dia = String(hoje.getDate()).padStart(2, '0');
        const mes = String(hoje.getMonth() + 1).padStart(2, '0');
        const ano = hoje.getFullYear();
        document.getElementById('data_utilizacao').value = `${dia}/${mes}/${ano}`;

        // Mostrar informações do produto
        document.getElementById('produto_id').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const peso = option.getAttribute('data-peso');
            const unidade = option.getAttribute('data-unidade');
            const estoque = option.getAttribute('data-estoque');
            
            if (peso && estoque) {
                document.getElementById('produtoInfo').innerHTML = 
                    `<i class="bi bi-info-circle"></i> Peso por pacote: ${parseFloat(peso).toFixed(3)} ${unidade} | 
                    Estoque disponível: <strong>${estoque} pacotes</strong>`;
                
                // Limpar quantidade e resetar validação
                document.getElementById('quantidade_pacotes').value = '';
                document.getElementById('quantidade_pacotes').max = estoque;
                document.getElementById('estoqueAlert').textContent = '';
                calcularPeso();
            } else {
                document.getElementById('produtoInfo').textContent = '';
            }
        });

        // Calcular peso a ser utilizado
        function calcularPeso() {
            const option = document.getElementById('produto_id').options[document.getElementById('produto_id').selectedIndex];
            const pesoPacote = parseFloat(option.getAttribute('data-peso')) || 0;
            const unidade = option.getAttribute('data-unidade');
            const estoqueDisponivel = parseInt(option.getAttribute('data-estoque')) || 0;
            const quantidade = parseInt(document.getElementById('quantidade_pacotes').value) || 0;
            
            if (quantidade > 0 && pesoPacote > 0) {
                const pesoTotal = quantidade * pesoPacote;
                document.getElementById('calculoInfo').style.display = 'block';
                document.getElementById('calculoTexto').innerHTML = 
                    `<strong>${pesoTotal.toFixed(3)} ${unidade}</strong> 
                    (${quantidade} pacotes × ${pesoPacote.toFixed(3)} ${unidade}/pacote)`;
                
                // Verificar se quantidade excede estoque
                if (quantidade > estoqueDisponivel) {
                    document.getElementById('estoqueAlert').innerHTML = 
                        '<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> Quantidade maior que estoque disponível!</span>';
                } else {
                    document.getElementById('estoqueAlert').innerHTML = 
                        `<span class="text-success"><i class="bi bi-check-circle"></i> Restará ${estoqueDisponivel - quantidade} pacotes no estoque</span>`;
                }
            } else {
                document.getElementById('calculoInfo').style.display = 'none';
                document.getElementById('estoqueAlert').textContent = '';
            }
        }

        document.getElementById('quantidade_pacotes').addEventListener('input', calcularPeso);

        // Validação do formulário
        document.getElementById('formSaida').addEventListener('submit', function(e) {
            const data = document.getElementById('data_utilizacao').value;
            const regex = /^\d{2}\/\d{2}\/\d{4}$/;
            
            if (!regex.test(data)) {
                e.preventDefault();
                alert('Por favor, insira uma data válida no formato dd/mm/aaaa');
                return false;
            }
            
            const option = document.getElementById('produto_id').options[document.getElementById('produto_id').selectedIndex];
            const estoqueDisponivel = parseInt(option.getAttribute('data-estoque')) || 0;
            const quantidade = parseInt(document.getElementById('quantidade_pacotes').value) || 0;
            
            if (quantidade > estoqueDisponivel) {
                e.preventDefault();
                alert('A quantidade informada é maior que o estoque disponível!');
                return false;
            }
        });
    </script>
</body>
</html>