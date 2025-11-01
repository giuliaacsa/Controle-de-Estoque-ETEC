<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();

// Buscar produtos agrupados por categoria
$stmt = $db->query("
    SELECT 
        c.id as categoria_id,
        c.nome as categoria,
        c.cor,
        p.id as produto_id,
        p.nome as produto,
        p.estoque_atual,
        p.estoque_minimo,
        p.peso_pacote,
        p.unidade_medida,
        ROUND(p.estoque_atual * p.peso_pacote, 2) as peso_total,
        CASE 
            WHEN p.estoque_atual <= p.estoque_minimo THEN 'critical'
            WHEN p.estoque_atual <= (p.estoque_minimo * 1.5) THEN 'low'
            ELSE 'normal'
        END as status
    FROM categorias c
    LEFT JOIN produtos p ON c.id = p.categoria_id AND p.ativo = TRUE
    ORDER BY c.nome, p.nome
");

$categorias = [];
while ($row = $stmt->fetch()) {
    if (!isset($categorias[$row['categoria_id']])) {
        $categorias[$row['categoria_id']] = [
            'nome' => $row['categoria'],
            'cor' => $row['cor'],
            'produtos' => []
        ];
    }
    if ($row['produto_id']) {
        $categorias[$row['categoria_id']]['produtos'][] = $row;
    }
}

$icones = [
    'Grãos e Cereais' => 'bi-basket',
    'Carnes' => 'bi-shop',
    'Frutas e Legumes' => 'bi-tree',
    'Laticínios' => 'bi-cup-straw',
    'Condimentos e Temperos' => 'bi-droplet',
    'Biscoitos e Doces' => 'bi-cookie',
    'Óleos e Gorduras' => 'bi-droplet-fill'
];

// Função para calcular quantidade de caixas (média das últimas entradas)
function calcularCaixasEstimadas($db, $produto_id, $estoque_atual, $peso_pacote) {
    // Se não há estoque, retorna 0
    if ($estoque_atual <= 0) {
        return '0';
    }
    
    // Buscar peso médio por caixa das últimas 3 entradas
    $stmt = $db->prepare("
        SELECT AVG(peso_por_caixa) as peso_medio_caixa
        FROM (
            SELECT peso_por_caixa 
            FROM entradas 
            WHERE produto_id = ? 
            ORDER BY data_recebimento DESC 
            LIMIT 3
        ) as ultimas_entradas
    ");
    $stmt->execute([$produto_id]);
    $resultado = $stmt->fetch();
    
    if ($resultado && $resultado['peso_medio_caixa'] > 0) {
        $peso_total_estoque = $estoque_atual * $peso_pacote;
        $caixas_estimadas = $peso_total_estoque / $resultado['peso_medio_caixa'];
        
        // Se for menos de 1 caixa, mostrar com 2 decimais
        if ($caixas_estimadas < 1) {
            return number_format($caixas_estimadas, 2, ',', '.');
        }
        // Se for mais de 1, mostrar com 1 decimal
        return number_format($caixas_estimadas, 1, ',', '.');
    }
    
    // Se não houver entradas registradas, tentar estimar baseado em um padrão
    return '~' . number_format($estoque_atual / 10, 1, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Estoque</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .category-card-collapsed {
            background: var(--etec-white);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            border: 2px solid;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .category-card-collapsed:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .category-card-collapsed.active {
            border-width: 3px;
        }
        
        .category-products {
            max-height: 0;
            overflow: hidden;
            margin-top: 0;
            transition: max-height 0.4s ease-out, margin-top 0.4s ease-out;
        }
        
        .category-products.show {
            max-height: 5000px;
            margin-top: 20px;
        }
        
        .category-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .category-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .category-stats {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            display: block;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .expand-icon {
            font-size: 1.5rem;
            transition: transform 0.3s ease;
        }
        
        .category-card-collapsed.active .expand-icon {
            transform: rotate(180deg);
        }
        
        .product-card {
            opacity: 1;
            transition: opacity 0.3s ease;
        }
        
        .product-card.hidden {
            display: none !important;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="header-card">
                    <h1><i class="bi bi-box-seam-fill"></i> Estoque Atual</h1>
                    <p class="text-white mb-0">Visualização completa dos produtos em estoque</p>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <input type="text" class="form-control" id="searchProduct" placeholder="🔍 Buscar produto...">
            </div>
            <div class="col-md-6 mb-3">
                <select class="form-select" id="filterStatus">
                    <option value="all">Todos os Status</option>
                    <option value="normal">Normal</option>
                    <option value="low">Estoque Baixo</option>
                    <option value="critical">Estoque Crítico</option>
                </select>
            </div>
        </div>

        <?php foreach ($categorias as $cat_id => $cat): ?>
            <?php if (!empty($cat['produtos'])): ?>
                <?php
                $total_produtos = count($cat['produtos']);
                $total_pacotes = array_sum(array_column($cat['produtos'], 'estoque_atual'));
                $produtos_criticos = count(array_filter($cat['produtos'], fn($p) => $p['status'] === 'critical'));
                ?>
                <div class="category-card-collapsed" 
                     style="border-color: <?php echo $cat['cor']; ?>;"
                     data-category="cat-<?php echo $cat_id; ?>">
                    
                    <div class="category-summary" onclick="toggleCategory('cat-<?php echo $cat_id; ?>')">
                        <div class="category-info">
                            <div class="category-icon" style="background-color: <?php echo $cat['cor']; ?>">
                                <i class="<?php echo $icones[$cat['nome']] ?? 'bi-box'; ?>"></i>
                            </div>
                            <div>
                                <h3 class="category-title mb-0" style="color: <?php echo $cat['cor']; ?>">
                                    <?php echo htmlspecialchars($cat['nome']); ?>
                                </h3>
                            </div>
                        </div>
                        
                        <div class="category-stats">
                            <div class="stat-item">
                                <span class="stat-number" style="color: <?php echo $cat['cor']; ?>">
                                    <?php echo $total_produtos; ?>
                                </span>
                                <span class="stat-label">Produtos</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number" style="color: <?php echo $cat['cor']; ?>">
                                    <?php echo $total_pacotes; ?>
                                </span>
                                <span class="stat-label">Pacotes</span>
                            </div>
                            <?php if ($produtos_criticos > 0): ?>
                                <div class="stat-item">
                                    <span class="stat-number text-danger">
                                        <?php echo $produtos_criticos; ?>
                                    </span>
                                    <span class="stat-label">Críticos</span>
                                </div>
                            <?php endif; ?>
                            <i class="bi bi-chevron-down expand-icon" style="color: <?php echo $cat['cor']; ?>"></i>
                        </div>
                    </div>
                    
                    <div class="category-products" id="category-cat-<?php echo $cat_id; ?>">
                        <div class="row">
                            <?php foreach ($cat['produtos'] as $prod): ?>
                                <?php 
                                $caixas_estimadas = calcularCaixasEstimadas($db, $prod['produto_id'], $prod['estoque_atual'], $prod['peso_pacote']);
                                ?>
                                <div class="col-lg-6 col-xl-4 mb-3 product-card" 
                                     data-status="<?php echo $prod['status']; ?>" 
                                     data-name="<?php echo strtolower($prod['produto']); ?>"
                                     data-category="cat-<?php echo $cat_id; ?>">
                                    <div class="product-item" style="border-color: <?php echo $cat['cor']; ?>">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="product-name" style="color: <?php echo $cat['cor']; ?>">
                                                <?php echo htmlspecialchars($prod['produto']); ?>
                                            </div>
                                            <span class="stock-badge stock-<?php echo $prod['status']; ?>">
                                                <?php 
                                                if ($prod['status'] == 'critical') echo 'Crítico';
                                                elseif ($prod['status'] == 'low') echo 'Baixo';
                                                else echo 'Normal';
                                                ?>
                                            </span>
                                        </div>
                                        <div class="product-info">
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <small class="text-muted"><strong>Estoque:</strong></small><br>
                                                    <strong style="color: <?php echo $cat['cor']; ?>; font-size: 1.3rem;">
                                                        <?php echo $prod['estoque_atual']; ?>
                                                    </strong> 
                                                    <small class="text-muted">pacotes</small>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted"><strong>Caixas (aprox.):</strong></small><br>
                                                    <strong style="color: <?php echo $cat['cor']; ?>; font-size: 1.3rem;">
                                                        <?php echo $caixas_estimadas; ?>
                                                    </strong> 
                                                    <small class="text-muted">cx</small>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted"><strong>Mínimo:</strong></small><br>
                                                    <span style="font-size: 1.05rem;">
                                                        <?php echo $prod['estoque_minimo']; ?> pct
                                                    </span>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted"><strong>Peso/Pacote:</strong></small><br>
                                                    <span style="font-size: 1.05rem;">
                                                        <?php echo formatarNumero($prod['peso_pacote'], 3); ?> 
                                                        <?php echo $prod['unidade_medida']; ?>
                                                    </span>
                                                </div>
                                                <div class="col-12 mt-2">
                                                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                                        <small class="text-muted"><strong>Peso Total:</strong></small>
                                                        <strong style="color: <?php echo $cat['cor']; ?>; font-size: 1.1rem;">
                                                            <?php echo formatarNumero($prod['peso_total'], 2); ?> 
                                                            <?php echo $prod['unidade_medida']; ?>
                                                        </strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleCategory(categoryId) {
            const categoryCard = document.querySelector(`[data-category="${categoryId}"]`);
            const productsDiv = document.getElementById(`category-${categoryId}`);
            
            if (!categoryCard || !productsDiv) return;
            
            categoryCard.classList.toggle('active');
            productsDiv.classList.toggle('show');
        }

        // Busca de produtos
        document.getElementById('searchProduct').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const products = document.querySelectorAll('.product-card');
            const categories = document.querySelectorAll('.category-card-collapsed');
            
            if (searchTerm === '') {
                // Se a busca estiver vazia, fechar todas as categorias
                products.forEach(product => {
                    product.classList.remove('hidden');
                });
                categories.forEach(cat => {
                    cat.style.display = '';
                    cat.classList.remove('active');
                    const categoryId = cat.getAttribute('data-category');
                    const productsDiv = document.getElementById(`category-${categoryId}`);
                    if (productsDiv) productsDiv.classList.remove('show');
                });
                return;
            }
            
            // Rastrear quais categorias têm produtos que correspondem
            const categoriesWithProducts = new Set();
            
            products.forEach(product => {
                const productName = product.getAttribute('data-name');
                const categoryId = product.getAttribute('data-category');
                
                if (productName.includes(searchTerm)) {
                    product.classList.remove('hidden');
                    categoriesWithProducts.add(categoryId);
                } else {
                    product.classList.add('hidden');
                }
            });
            
            // Mostrar/ocultar categorias baseado na busca
            categories.forEach(cat => {
                const categoryId = cat.getAttribute('data-category');
                const productsDiv = document.getElementById(`category-${categoryId}`);
                
                if (categoriesWithProducts.has(categoryId)) {
                    cat.style.display = '';
                    cat.classList.add('active');
                    if (productsDiv) productsDiv.classList.add('show');
                } else {
                    cat.style.display = 'none';
                }
            });
        });

        // Filtro por status
        document.getElementById('filterStatus').addEventListener('change', function() {
            const status = this.value;
            const products = document.querySelectorAll('.product-card');
            const categories = document.querySelectorAll('.category-card-collapsed');
            
            if (status === 'all') {
                products.forEach(product => product.classList.remove('hidden'));
                categories.forEach(cat => {
                    cat.style.display = '';
                    cat.classList.remove('active');
                    const categoryId = cat.getAttribute('data-category');
                    const productsDiv = document.getElementById(`category-${categoryId}`);
                    if (productsDiv) productsDiv.classList.remove('show');
                });
                return;
            }
            
            const categoriesWithProducts = new Set();
            
            products.forEach(product => {
                const productStatus = product.getAttribute('data-status');
                const categoryId = product.getAttribute('data-category');
                
                if (productStatus === status) {
                    product.classList.remove('hidden');
                    categoriesWithProducts.add(categoryId);
                } else {
                    product.classList.add('hidden');
                }
            });
            
            categories.forEach(cat => {
                const categoryId = cat.getAttribute('data-category');
                const productsDiv = document.getElementById(`category-${categoryId}`);
                
                if (categoriesWithProducts.has(categoryId)) {
                    cat.style.display = '';
                    cat.classList.add('active');
                    if (productsDiv) productsDiv.classList.add('show');
                } else {
                    cat.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>