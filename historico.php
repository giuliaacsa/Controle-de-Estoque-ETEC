<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();

$tipo_filtro = $_GET['tipo'] ?? '';
$produto_filtro = $_GET['produto'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

$sql = "SELECT * FROM v_historico_movimentacoes WHERE 1=1";
$params = [];

if ($tipo_filtro) {
    $sql .= " AND BINARY tipo = ?";
    $params[] = $tipo_filtro;
}

if ($produto_filtro) {
    $sql .= " AND produto LIKE ?";
    $params[] = "%{$produto_filtro}%";
}

if ($data_inicio) {
    $data_inicio_banco = formatarDataBanco($data_inicio);
    if ($data_inicio_banco) {
        $sql .= " AND data_movimentacao >= ?";
        $params[] = $data_inicio_banco;
    }
}

if ($data_fim) {
    $data_fim_banco = formatarDataBanco($data_fim);
    if ($data_fim_banco) {
        $sql .= " AND data_movimentacao <= ?";
        $params[] = $data_fim_banco;
    }
}

$sql .= " ORDER BY data_movimentacao DESC, created_at DESC LIMIT 100";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$movimentacoes = $stmt->fetchAll();

$stmt = $db->query("SELECT DISTINCT nome FROM produtos WHERE ativo = TRUE ORDER BY nome");
$produtos_lista = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Histórico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="header-card">
                    <h1><i class="bi bi-clock-history"></i> Histórico de Movimentações</h1>
                    <p class="text-muted mb-0">Entradas e saídas registradas</p>
                </div>
            </div>
        </div>

        <div class="card-custom mb-4">
            <h5><i class="bi bi-funnel"></i> Filtros</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select name="tipo" class="form-select">
                        <option value="">Todos</option>
                        <option value="ENTRADA" <?php echo $tipo_filtro === 'ENTRADA' ? 'selected' : ''; ?>>Entrada</option>
                        <option value="SAÍDA" <?php echo $tipo_filtro === 'SAÍDA' ? 'selected' : ''; ?>>Saída</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Produto</label>
                    <input type="text" name="produto" class="form-control" 
                           placeholder="Nome do produto" value="<?php echo htmlspecialchars($produto_filtro); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data Início</label>
                    <input type="text" name="data_inicio" class="form-control datepicker" 
                           placeholder="dd/mm/aaaa" value="<?php echo htmlspecialchars($data_inicio); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data Fim</label>
                    <input type="text" name="data_fim" class="form-control datepicker" 
                           placeholder="dd/mm/aaaa" value="<?php echo htmlspecialchars($data_fim); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-etec w-100">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                </div>
            </form>
            <?php if ($tipo_filtro || $produto_filtro || $data_inicio || $data_fim): ?>
                <div class="mt-3">
                    <a href="historico.php" class="btn btn-sm btn-outline-etec">
                        <i class="bi bi-x-circle"></i> Limpar Filtros
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="bi bi-list-ul"></i> Movimentações (<?php echo count($movimentacoes); ?> registros)</h5>
                <div>
                    <a href="entrada.php" class="btn btn-sm btn-success">
                        <i class="bi bi-arrow-down-circle"></i> Nova Entrada
                    </a>
                    <a href="saida.php" class="btn btn-sm btn-danger">
                        <i class="bi bi-arrow-up-circle"></i> Nova Saída
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Quantidade</th>
                            <th>Lote</th>
                            <th>Responsável</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimentacoes)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Nenhuma movimentação encontrada
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($movimentacoes as $mov): ?>
                                <tr>
                                    <td><?php echo formatarData($mov['data_movimentacao']); ?></td>
                                    <td>
                                        <?php if ($mov['tipo'] == 'ENTRADA'): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-arrow-down-circle"></i> Entrada
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">
                                                <i class="bi bi-arrow-up-circle"></i> Saída
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($mov['produto']); ?></strong></td>
                                    <td>
                                        <span class="badge" style="background-color: var(--etec-wine);">
                                            <?php echo htmlspecialchars($mov['categoria']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold"><?php echo $mov['quantidade']; ?></span> pacotes
                                    </td>
                                    <td>
                                        <?php if ($mov['lote']): ?>
                                            <code><?php echo htmlspecialchars($mov['lote']); ?></code>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($mov['responsavel']): ?>
                                            <i class="bi bi-person"></i> 
                                            <?php echo htmlspecialchars($mov['responsavel']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.datepicker').forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) value = value.slice(0,2) + '/' + value.slice(2);
                if (value.length >= 5) value = value.slice(0,5) + '/' + value.slice(5,9);
                e.target.value = value;
            });
        });
    </script>
</body>
</html>