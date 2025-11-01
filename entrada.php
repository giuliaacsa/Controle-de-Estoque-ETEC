<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $produto_id = $_POST['produto_id'];
        $remessa = $_POST['remessa'];
        $lote = $_POST['lote'];
        $quantidade_caixas = $_POST['quantidade_caixas'];
        $peso_por_caixa = $_POST['peso_por_caixa'];
        $data_recebimento = formatarDataBanco($_POST['data_recebimento']);
        $recebido_por = $_POST['recebido_por'];
        $observacoes = $_POST['observacoes'] ?? '';
        
        // Buscar peso do pacote do produto
        $stmt = $db->prepare("SELECT peso_pacote FROM produtos WHERE id = ?");
        $stmt->execute([$produto_id]);
        $produto = $stmt->fetch();
        
        if (!$produto) {
            throw new Exception("Produto não encontrado!");
        }
        
        // Calcular peso total e quantidade de pacotes
        $peso_total = $quantidade_caixas * $peso_por_caixa;
        $quantidade_pacotes = floor($peso_total / $produto['peso_pacote']);
        
        // Inserir entrada
        $stmt = $db->prepare("
            INSERT INTO entradas 
            (produto_id, remessa, lote, quantidade_caixas, peso_por_caixa, peso_total, 
             quantidade_pacotes, data_recebimento, recebido_por, observacoes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $produto_id, $remessa, $lote, $quantidade_caixas, $peso_por_caixa,
            $peso_total, $quantidade_pacotes, $data_recebimento, $recebido_por, $observacoes
        ]);
        
        $message = "Entrada registrada com sucesso! {$quantidade_pacotes} pacotes adicionados ao estoque.";
        $messageType = "success";
        
    } catch (Exception $e) {
        $message = "Erro ao registrar entrada: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Buscar produtos ativos
$stmt = $db->query("
    SELECT p.id, p.nome, c.nome as categoria, p.peso_pacote, p.unidade_medida
    FROM produtos p
    INNER JOIN categorias c ON p.categoria_id = c.id
    WHERE p.ativo = TRUE
    ORDER BY c.nome, p.nome
");
$produtos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Registrar Entrada</title>
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
                    <h1><i class="bi bi-arrow-down-circle-fill"></i> Registrar Entrada</h1>
                    <p class="text-muted mb-0">Adicionar produtos ao estoque</p>
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
                    <h5><i class="bi bi-clipboard-plus"></i> Dados da Entrada</h5>
                    
                    <form method="POST" id="formEntrada">
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
                                                data-unidade="<?php echo $prod['unidade_medida']; ?>">
                                            <?php echo htmlspecialchars($prod['nome']); ?>
                                            (<?php echo formatarNumero($prod['peso_pacote'], 3); ?> <?php echo $prod['unidade_medida']; ?>/pct)
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if ($currentCategory != '') echo '</optgroup>'; ?>
                                </select>
                                <small class="form-text text-muted" id="produtoInfo"></small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="remessa" class="form-label">
                                    Remessa <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="remessa" name="remessa" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="lote" class="form-label">
                                    Lote <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="lote" name="lote" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="quantidade_caixas" class="form-label">
                                    Quantidade de Caixas <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="quantidade_caixas" 
                                       name="quantidade_caixas" min="1" step="1" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="peso_por_caixa" class="form-label">
                                    Peso por Caixa (kg) <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="peso_por_caixa" 
                                       name="peso_por_caixa" min="0.001" step="0.001" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="peso_total" class="form-label">
                                    Peso Total (kg)
                                </label>
                                <input type="text" class="form-control" id="peso_total" readonly>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <div class="alert alert-info" id="calculoInfo" style="display: none;">
                                    <i class="bi bi-calculator"></i>
                                    <strong>Cálculo Automático:</strong>
                                    <span id="calculoTexto"></span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="data_recebimento" class="form-label">
                                    Data de Recebimento <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="data_recebimento" 
                                       name="data_recebimento" placeholder="dd/mm/aaaa" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="recebido_por" class="form-label">
                                    Recebido Por
                                </label>
                                <input type="text" class="form-control" id="recebido_por" name="recebido_por">
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
                                <i class="bi bi-save"></i> Registrar Entrada
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
        document.getElementById('data_recebimento').addEventListener('input', function(e) {
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
        document.getElementById('data_recebimento').value = `${dia}/${mes}/${ano}`;

        // Mostrar informações do produto
        document.getElementById('produto_id').addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            const peso = option.getAttribute('data-peso');
            const unidade = option.getAttribute('data-unidade');
            
            if (peso) {
                document.getElementById('produtoInfo').textContent = 
                    `Peso por pacote: ${parseFloat(peso).toFixed(3)} ${unidade}`;
                calcularPacotes();
            } else {
                document.getElementById('produtoInfo').textContent = '';
            }
        });

        // Calcular peso total e quantidade de pacotes
        function calcularPacotes() {
            const caixas = parseFloat(document.getElementById('quantidade_caixas').value) || 0;
            const pesoCaixa = parseFloat(document.getElementById('peso_por_caixa').value) || 0;
            const option = document.getElementById('produto_id').options[document.getElementById('produto_id').selectedIndex];
            const pesoPacote = parseFloat(option.getAttribute('data-peso')) || 0;
            const unidade = option.getAttribute('data-unidade');
            
            if (caixas > 0 && pesoCaixa > 0) {
                const pesoTotal = caixas * pesoCaixa;
                document.getElementById('peso_total').value = pesoTotal.toFixed(3);
                
                if (pesoPacote > 0) {
                    const qtdPacotes = Math.floor(pesoTotal / pesoPacote);
                    document.getElementById('calculoInfo').style.display = 'block';
                    document.getElementById('calculoTexto').innerHTML = 
                        `Serão adicionados <strong>${qtdPacotes} pacotes</strong> ao estoque 
                        (${pesoTotal.toFixed(3)} kg ÷ ${pesoPacote.toFixed(3)} ${unidade}/pacote)`;
                }
            } else {
                document.getElementById('peso_total').value = '';
                document.getElementById('calculoInfo').style.display = 'none';
            }
        }

        document.getElementById('quantidade_caixas').addEventListener('input', calcularPacotes);
        document.getElementById('peso_por_caixa').addEventListener('input', calcularPacotes);

        // Validação do formulário
        document.getElementById('formEntrada').addEventListener('submit', function(e) {
            const data = document.getElementById('data_recebimento').value;
            const regex = /^\d{2}\/\d{2}\/\d{4}$/;
            
            if (!regex.test(data)) {
                e.preventDefault();
                alert('Por favor, insira uma data válida no formato dd/mm/aaaa');
                return false;
            }
        });
    </script>
</body>
</html>