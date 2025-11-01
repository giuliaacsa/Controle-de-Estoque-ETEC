<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Processar ações (adicionar, editar, excluir)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    try {
        if ($action === 'adicionar') {
            $stmt = $db->prepare("
                INSERT INTO produtos (nome, categoria_id, peso_pacote, unidade_medida, estoque_minimo)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['nome'],
                $_POST['categoria_id'],
                $_POST['peso_pacote'],
                $_POST['unidade_medida'],
                $_POST['estoque_minimo']
            ]);
            $message = "Produto adicionado com sucesso!";
            $messageType = "success";
            
        } elseif ($action === 'editar') {
            $stmt = $db->prepare("
                UPDATE produtos 
                SET nome = ?, categoria_id = ?, peso_pacote = ?, 
                    unidade_medida = ?, estoque_minimo = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['nome'],
                $_POST['categoria_id'],
                $_POST['peso_pacote'],
                $_POST['unidade_medida'],
                $_POST['estoque_minimo'],
                $_POST['produto_id']
            ]);
            $message = "Produto atualizado com sucesso!";
            $messageType = "success";
            
        } elseif ($action === 'excluir') {
            $stmt = $db->prepare("UPDATE produtos SET ativo = FALSE WHERE id = ?");
            $stmt->execute([$_POST['produto_id']]);
            $message = "Produto desativado com sucesso!";
            $messageType = "success";
        }
        
    } catch (Exception $e) {
        $message = "Erro: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Buscar categorias
$stmt = $db->query("SELECT * FROM categorias ORDER BY nome");
$categorias = $stmt->fetchAll();

// Buscar produtos
$stmt = $db->query("
    SELECT p.*, c.nome as categoria_nome
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
    <title><?php echo SITE_NAME; ?> - Gerenciar Produtos</title>
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
                    <h1><i class="bi bi-gear-fill"></i> Gerenciar Produtos</h1>
                    <p class="text-muted mb-0">Adicionar, editar ou remover produtos</p>
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
            <div class="col-12 mb-3">
                <button class="btn btn-etec" data-bs-toggle="modal" data-bs-target="#modalAdicionar">
                    <i class="bi bi-plus-circle"></i> Adicionar Novo Produto
                </button>
            </div>
        </div>

        <div class="card-custom">
            <h5><i class="bi bi-list"></i> Produtos Cadastrados (<?php echo count($produtos); ?>)</h5>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Peso/Pacote</th>
                            <th>Estoque Atual</th>
                            <th>Estoque Mínimo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $prod): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($prod['nome']); ?></strong></td>
                                <td>
                                    <span class="badge" style="background-color: var(--etec-wine);">
                                        <?php echo htmlspecialchars($prod['categoria_nome']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo formatarNumero($prod['peso_pacote'], 3); ?> 
                                    <?php echo $prod['unidade_medida']; ?>
                                </td>
                                <td>
                                    <span class="fw-bold" style="color: var(--etec-red);">
                                        <?php echo $prod['estoque_atual']; ?>
                                    </span> pacotes
                                </td>
                                <td><?php echo $prod['estoque_minimo']; ?> pacotes</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editarProduto(<?php echo htmlspecialchars(json_encode($prod)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="excluirProduto(<?php echo $prod['id']; ?>, '<?php echo htmlspecialchars($prod['nome']); ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar Produto -->
    <div class="modal fade" id="modalAdicionar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Adicionar Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="adicionar">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nome do Produto <span class="text-danger">*</span></label>
                            <input type="text" name="nome" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Categoria <span class="text-danger">*</span></label>
                            <select name="categoria_id" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Peso/Pacote <span class="text-danger">*</span></label>
                                <input type="number" name="peso_pacote" class="form-control" 
                                       step="0.001" min="0.001" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unidade <span class="text-danger">*</span></label>
                                <select name="unidade_medida" class="form-select" required>
                                    <option value="kg">kg</option>
                                    <option value="g">g</option>
                                    <option value="l">l</option>
                                    <option value="ml">ml</option>
                                    <option value="unidade">unidade</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estoque Mínimo <span class="text-danger">*</span></label>
                            <input type="number" name="estoque_minimo" class="form-control" 
                                   min="0" step="1" value="10" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-etec">
                            <i class="bi bi-save"></i> Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Produto -->
    <div class="modal fade" id="modalEditar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="editar">
                    <input type="hidden" name="produto_id" id="edit_produto_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nome do Produto <span class="text-danger">*</span></label>
                            <input type="text" name="nome" id="edit_nome" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Categoria <span class="text-danger">*</span></label>
                            <select name="categoria_id" id="edit_categoria_id" class="form-select" required>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Peso/Pacote <span class="text-danger">*</span></label>
                                <input type="number" name="peso_pacote" id="edit_peso_pacote" 
                                       class="form-control" step="0.001" min="0.001" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unidade <span class="text-danger">*</span></label>
                                <select name="unidade_medida" id="edit_unidade_medida" class="form-select" required>
                                    <option value="kg">kg</option>
                                    <option value="g">g</option>
                                    <option value="l">l</option>
                                    <option value="ml">ml</option>
                                    <option value="unidade">unidade</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estoque Mínimo <span class="text-danger">*</span></label>
                            <input type="number" name="estoque_minimo" id="edit_estoque_minimo" 
                                   class="form-control" min="0" step="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-etec">
                            <i class="bi bi-save"></i> Atualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Form oculto para excluir -->
    <form method="POST" id="formExcluir" style="display: none;">
        <input type="hidden" name="action" value="excluir">
        <input type="hidden" name="produto_id" id="excluir_produto_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarProduto(produto) {
            document.getElementById('edit_produto_id').value = produto.id;
            document.getElementById('edit_nome').value = produto.nome;
            document.getElementById('edit_categoria_id').value = produto.categoria_id;
            document.getElementById('edit_peso_pacote').value = produto.peso_pacote;
            document.getElementById('edit_unidade_medida').value = produto.unidade_medida;
            document.getElementById('edit_estoque_minimo').value = produto.estoque_minimo;
            
            new bootstrap.Modal(document.getElementById('modalEditar')).show();
        }

        function excluirProduto(id, nome) {
            if (confirm(`Tem certeza que deseja desativar o produto "${nome}"?\n\nO produto não será excluído, apenas desativado e não aparecerá mais nas listagens.`)) {
                document.getElementById('excluir_produto_id').value = id;
                document.getElementById('formExcluir').submit();
            }
        }
    </script>
</body>
</html>