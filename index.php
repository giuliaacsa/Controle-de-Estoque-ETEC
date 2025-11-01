<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();

// Buscar estatísticas
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_produtos,
        SUM(estoque_atual) as total_pacotes,
        COUNT(CASE WHEN estoque_atual <= estoque_minimo THEN 1 END) as produtos_criticos
    FROM produtos WHERE ativo = TRUE
");
$stats = $stmt->fetch();

// Buscar produtos com estoque crítico
$stmt = $db->query("
    SELECT p.nome, p.estoque_atual, p.estoque_minimo, c.cor
    FROM produtos p
    INNER JOIN categorias c ON p.categoria_id = c.id
    WHERE p.ativo = TRUE AND p.estoque_atual <= p.estoque_minimo
    ORDER BY p.estoque_atual ASC
    LIMIT 10
");
$produtos_criticos = $stmt->fetchAll();

// Buscar movimentações recentes
$stmt = $db->query("
    SELECT * FROM v_historico_movimentacoes
    LIMIT 10
");
$movimentacoes_recentes = $stmt->fetchAll();

// Dados para gráficos
$stmt = $db->query("
    SELECT 
        c.nome as categoria,
        COUNT(p.id) as total_produtos,
        COALESCE(SUM(p.estoque_atual), 0) as total_pacotes
    FROM categorias c
    LEFT JOIN produtos p ON c.id = p.categoria_id AND p.ativo = TRUE
    GROUP BY c.id, c.nome
    HAVING total_produtos > 0
    ORDER BY c.nome
");
$dados_categorias = $stmt->fetchAll();

$stmt = $db->query("
    SELECT 
        COUNT(CASE WHEN estoque_atual > (estoque_minimo * 1.5) THEN 1 END) as normal,
        COUNT(CASE WHEN estoque_atual > estoque_minimo AND estoque_atual <= (estoque_minimo * 1.5) THEN 1 END) as baixo,
        COUNT(CASE WHEN estoque_atual <= estoque_minimo THEN 1 END) as critico
    FROM produtos 
    WHERE ativo = TRUE
");
$dados_status = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Dashboard</title>
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
                    <h1><i class="bi bi-house-door-fill"></i> Dashboard</h1>
                    <p class="text-muted mb-0">Visão geral do estoque</p>
                </div>
            </div>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_produtos']; ?></h3>
                        <p>Produtos Cadastrados</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card stat-success">
                    <div class="stat-icon">
                        <i class="bi bi-boxes"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo formatarNumero($stats['total_pacotes'], 0); ?></h3>
                        <p>Pacotes em Estoque</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card stat-danger">
                    <div class="stat-icon">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['produtos_criticos']; ?></h3>
                        <p>Produtos Críticos</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Gráficos -->
            <div class="col-lg-6 mb-4">
                <div class="card-custom">
                    <h5><i class="bi bi-bar-chart-fill"></i> Estoque por Categoria</h5>
                    <div style="height: 300px; position: relative;">
                        <canvas id="chartCategoria"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card-custom">
                    <h5><i class="bi bi-pie-chart-fill"></i> Status dos Produtos</h5>
                    <div style="height: 300px; position: relative;">
                        <canvas id="chartStatus"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Produtos com Estoque Crítico -->
            <div class="col-lg-4 mb-4">
                <div class="card-custom">
                    <h5><i class="bi bi-exclamation-circle-fill"></i> Alertas de Estoque</h5>
                    <?php if (empty($produtos_criticos)): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> Nenhum produto com estoque crítico!
                        </div>
                    <?php else: ?>
                        <div class="alert-list">
                            <?php foreach ($produtos_criticos as $prod): ?>
                                <div class="alert-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($prod['nome']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                Atual: <?php echo $prod['estoque_atual']; ?> | 
                                                Mínimo: <?php echo $prod['estoque_minimo']; ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-arrow-down"></i>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Movimentações Recentes -->
            <div class="col-lg-8 mb-4">
                <div class="card-custom">
                    <h5><i class="bi bi-clock-history"></i> Últimas Movimentações</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Produto</th>
                                    <th>Categoria</th>
                                    <th>Quantidade</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($movimentacoes_recentes)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            Nenhuma movimentação registrada
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($movimentacoes_recentes as $mov): ?>
                                        <tr>
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
                                            <td><?php echo htmlspecialchars($mov['produto']); ?></td>
                                            <td>
                                                <span class="badge" style="background-color: var(--etec-wine);">
                                                    <?php echo htmlspecialchars($mov['categoria']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $mov['quantidade']; ?> pct</td>
                                            <td><?php echo formatarData($mov['data_movimentacao']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="historico.php" class="btn btn-outline-etec">
                            <i class="bi bi-clock-history"></i> Ver Histórico Completo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Dados do PHP para JavaScript
        const dadosCategorias = <?php echo json_encode($dados_categorias); ?>;
        const dadosStatus = <?php echo json_encode($dados_status); ?>;

        // Gráfico de Estoque por Categoria
        const ctxCategoria = document.getElementById('chartCategoria');
        if (ctxCategoria) {
            new Chart(ctxCategoria, {
                type: 'bar',
                data: {
                    labels: dadosCategorias.map(d => d.categoria),
                    datasets: [{
                        label: 'Pacotes em Estoque',
                        data: dadosCategorias.map(d => parseInt(d.total_pacotes)),
                        backgroundColor: [
                            'rgba(196, 30, 58, 0.8)',
                            'rgba(139, 0, 0, 0.8)',
                            'rgba(165, 42, 42, 0.8)',
                            'rgba(220, 20, 60, 0.8)',
                            'rgba(178, 34, 34, 0.8)',
                            'rgba(205, 92, 92, 0.8)',
                            'rgba(128, 0, 0, 0.8)'
                        ],
                        borderColor: [
                            'rgba(196, 30, 58, 1)',
                            'rgba(139, 0, 0, 1)',
                            'rgba(165, 42, 42, 1)',
                            'rgba(220, 20, 60, 1)',
                            'rgba(178, 34, 34, 1)',
                            'rgba(205, 92, 92, 1)',
                            'rgba(128, 0, 0, 1)'
                        ],
                        borderWidth: 2,
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                font: {
                                    size: 12,
                                    weight: 'bold',
                                    family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                                },
                                color: '#1a1a1a',
                                padding: 15
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y.toLocaleString('pt-BR') + ' pacotes';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString('pt-BR');
                                },
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 11
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Gráfico de Status dos Produtos
        const ctxStatus = document.getElementById('chartStatus');
        if (ctxStatus) {
            new Chart(ctxStatus, {
                type: 'doughnut',
                data: {
                    labels: ['Normal', 'Estoque Baixo', 'Crítico'],
                    datasets: [{
                        data: [
                            parseInt(dadosStatus.normal) || 0,
                            parseInt(dadosStatus.baixo) || 0,
                            parseInt(dadosStatus.critico) || 0
                        ],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(220, 53, 69, 0.8)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(220, 53, 69, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                font: {
                                    size: 13,
                                    weight: 'bold',
                                    family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                                },
                                padding: 20,
                                color: '#1a1a1a',
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const value = context.parsed;
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return context.label + ': ' + value + ' produtos (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>