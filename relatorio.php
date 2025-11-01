<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use TCPDF as TCPDF;

$db = Database::getInstance()->getConnection();

if (isset($_GET['gerar_pdf'])) {
    $tipo = $_GET['tipo'] ?? 'completo';
    
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');
    
    $pdf->SetCreator('ETEC Bragança Paulista');
    $pdf->SetAuthor('Sistema de Controle de Estoque');
    $pdf->SetTitle('Relatório de Estoque');
    
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(TRUE, 10);
    
    $pdf->AddPage();
    
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(196, 30, 58);
    $pdf->Cell(0, 10, 'ETEC DE BRAGANÇA PAULISTA', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 8, 'Relatório de Controle de Estoque', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 6, 'Gerado em: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    $pdf->Ln(5);
    
    if ($tipo === 'completo') {
        $stmt = $db->query("
            SELECT * FROM v_estoque_completo
            ORDER BY categoria, produto
        ");
        $produtos = $stmt->fetchAll();
        
        $currentCategory = '';
        
        foreach ($produtos as $prod) {
            if ($currentCategory != $prod['categoria']) {
                $currentCategory = $prod['categoria'];
                
                $pdf->SetFillColor(196, 30, 58);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->Cell(0, 8, $prod['categoria'], 0, 1, 'L', true);
                
                $pdf->SetFillColor(220, 220, 220);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->Cell(80, 6, 'Produto', 1, 0, 'L', true);
                $pdf->Cell(30, 6, 'Peso/Pacote', 1, 0, 'C', true);
                $pdf->Cell(30, 6, 'Estoque Atual', 1, 0, 'C', true);
                $pdf->Cell(30, 6, 'Peso Total', 1, 0, 'C', true);
                $pdf->Cell(30, 6, 'Est. Mínimo', 1, 0, 'C', true);
                $pdf->Cell(77, 6, 'Status', 1, 1, 'C', true);
            }
            
            $pdf->SetFont('helvetica', '', 8);
            
            if ($prod['status_estoque'] == 'Crítico') {
                $pdf->SetTextColor(139, 0, 0);
            } elseif ($prod['status_estoque'] == 'Baixo') {
                $pdf->SetTextColor(255, 140, 0);
            } else {
                $pdf->SetTextColor(0, 0, 0);
            }
            
            $pdf->Cell(80, 6, $prod['produto'], 1, 0, 'L');
            $pdf->Cell(30, 6, number_format($prod['peso_pacote'], 3, ',', '.') . ' ' . $prod['unidade_medida'], 1, 0, 'C');
            $pdf->Cell(30, 6, $prod['estoque_atual'] . ' pct', 1, 0, 'C');
            $pdf->Cell(30, 6, number_format($prod['peso_total_estoque'], 2, ',', '.') . ' ' . $prod['unidade_medida'], 1, 0, 'C');
            $pdf->Cell(30, 6, $prod['estoque_minimo'] . ' pct', 1, 0, 'C');
            $pdf->Cell(77, 6, $prod['status_estoque'], 1, 1, 'C');
            
            $pdf->SetTextColor(0, 0, 0);
        }
        
    } elseif ($tipo === 'critico') {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(139, 0, 0);
        $pdf->Cell(0, 8, 'PRODUTOS COM ESTOQUE CRÍTICO', 0, 1, 'L');
        $pdf->Ln(2);
        
        $stmt = $db->query("
            SELECT * FROM v_estoque_completo
            WHERE BINARY status_estoque IN ('Crítico', 'Baixo')
            ORDER BY estoque_atual ASC
        ");
        $produtos = $stmt->fetchAll();
        
        $pdf->SetFillColor(139, 0, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(100, 7, 'Produto', 1, 0, 'L', true);
        $pdf->Cell(50, 7, 'Categoria', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Estoque Atual', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Est. Mínimo', 1, 0, 'C', true);
        $pdf->Cell(57, 7, 'Status', 1, 1, 'C', true);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 8);
        
        foreach ($produtos as $prod) {
            if ($prod['status_estoque'] == 'Crítico') {
                $pdf->SetFillColor(255, 200, 200);
            } else {
                $pdf->SetFillColor(255, 240, 200);
            }
            
            $pdf->Cell(100, 6, $prod['produto'], 1, 0, 'L', true);
            $pdf->Cell(50, 6, $prod['categoria'], 1, 0, 'C', true);
            $pdf->Cell(35, 6, $prod['estoque_atual'] . ' pct', 1, 0, 'C', true);
            $pdf->Cell(35, 6, $prod['estoque_minimo'] . ' pct', 1, 0, 'C', true);
            $pdf->SetTextColor(139, 0, 0);
            $pdf->Cell(57, 6, $prod['status_estoque'], 1, 1, 'C', true);
            $pdf->SetTextColor(0, 0, 0);
        }
    }
    
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->SetTextColor(128, 128, 128);
    $pdf->Cell(0, 5, 'Sistema de Controle de Estoque - ETEC Bragança Paulista', 0, 1, 'C');
    
    $pdf->Output('relatorio_estoque_' . date('Ymd_His') . '.pdf', 'I');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Relatórios</title>
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
                    <h1><i class="bi bi-file-earmark-pdf-fill"></i> Relatórios</h1>
                    <p class="text-muted mb-0">Gerar e exportar relatórios do estoque</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card-custom h-100">
                    <div class="text-center mb-3">
                        <i class="bi bi-file-earmark-text" style="font-size: 4rem; color: var(--etec-red);"></i>
                    </div>
                    <h5 class="text-center mb-3">Relatório Completo</h5>
                    <p class="text-muted text-center">
                        Relatório detalhado com todos os produtos em estoque, 
                        organizados por categoria com informações completas.
                    </p>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i> 
                            Todos os produtos
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i> 
                            Organizado por categorias
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i> 
                            Estoque atual e mínimo
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i> 
                            Status de cada produto
                        </li>
                    </ul>
                    <div class="text-center mt-4">
                        <a href="?gerar_pdf=1&tipo=completo" class="btn btn-etec" target="_blank">
                            <i class="bi bi-file-pdf"></i> Gerar PDF
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card-custom h-100">
                    <div class="text-center mb-3">
                        <i class="bi bi-exclamation-triangle" style="font-size: 4rem; color: var(--etec-dark-red);"></i>
                    </div>
                    <h5 class="text-center mb-3">Produtos Críticos</h5>
                    <p class="text-muted text-center">
                        Relatório específico dos produtos com estoque baixo ou crítico, 
                        priorizados para reposição urgente.
                    </p>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-danger"></i> 
                            Apenas produtos críticos
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-danger"></i> 
                            Ordenado por urgência
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-danger"></i> 
                            Destaque visual de alertas
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-danger"></i> 
                            Lista de prioridades
                        </li>
                    </ul>
                    <div class="text-center mt-4">
                        <a href="?gerar_pdf=1&tipo=critico" class="btn btn-etec" target="_blank">
                            <i class="bi bi-file-pdf"></i> Gerar PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card-custom">
                    <h5><i class="bi bi-bar-chart"></i> Estatísticas do Estoque</h5>
                    
                    <?php
                    $stmt = $db->query("
                        SELECT 
                            COUNT(*) as total_produtos,
                            SUM(estoque_atual) as total_pacotes,
                            COUNT(CASE WHEN estoque_atual <= estoque_minimo THEN 1 END) as produtos_criticos,
                            COUNT(CASE WHEN estoque_atual > estoque_minimo AND estoque_atual <= (estoque_minimo * 1.5) THEN 1 END) as produtos_baixos,
                            COUNT(CASE WHEN estoque_atual > (estoque_minimo * 1.5) THEN 1 END) as produtos_normais
                        FROM produtos WHERE ativo = TRUE
                    ");
                    $stats = $stmt->fetch();
                    
                    $stmt = $db->query("
                        SELECT 
                            c.nome as categoria,
                            COUNT(p.id) as total,
                            SUM(p.estoque_atual) as pacotes
                        FROM categorias c
                        LEFT JOIN produtos p ON c.id = p.categoria_id AND p.ativo = TRUE
                        GROUP BY c.id, c.nome
                        HAVING total > 0
                        ORDER BY c.nome
                    ");
                    $por_categoria = $stmt->fetchAll();
                    ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h2 class="mb-0" style="color: var(--etec-red);">
                                    <?php echo $stats['total_produtos']; ?>
                                </h2>
                                <small class="text-muted">Total de Produtos</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h2 class="mb-0" style="color: var(--etec-red);">
                                    <?php echo formatarNumero($stats['total_pacotes'], 0); ?>
                                </h2>
                                <small class="text-muted">Total de Pacotes</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                                <h2 class="mb-0 text-success">
                                    <?php echo $stats['produtos_normais']; ?>
                                </h2>
                                <small class="text-muted">Normal</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center p-3 bg-warning bg-opacity-10 rounded">
                                <h2 class="mb-0 text-warning">
                                    <?php echo $stats['produtos_baixos']; ?>
                                </h2>
                                <small class="text-muted">Baixo</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="text-center p-3 bg-danger bg-opacity-10 rounded">
                                <h2 class="mb-0 text-danger">
                                    <?php echo $stats['produtos_criticos']; ?>
                                </h2>
                                <small class="text-muted">Crítico</small>
                            </div>
                        </div>
                    </div>

                    <h6 class="mb-3">Por Categoria</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Categoria</th>
                                    <th class="text-center">Produtos</th>
                                    <th class="text-center">Pacotes em Estoque</th>
                                    <th class="text-end">Percentual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($por_categoria as $cat): ?>
                                    <tr>
                                        <td>
                                            <span class="badge" style="background-color: var(--etec-wine);">
                                                <?php echo htmlspecialchars($cat['categoria']); ?>
                                            </span>
                                        </td>
                                        <td class="text-center"><?php echo $cat['total']; ?></td>
                                        <td class="text-center">
                                            <strong><?php echo formatarNumero($cat['pacotes'], 0); ?></strong>
                                        </td>
                                        <td class="text-end">
                                            <?php 
                                            $percentual = ($cat['pacotes'] / $stats['total_pacotes']) * 100;
                                            echo formatarNumero($percentual, 1) . '%';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>