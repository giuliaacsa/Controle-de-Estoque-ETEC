<nav class="navbar navbar-expand-lg navbar-dark bg-etec">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-building"></i>
            <strong>ETEC Bragança Paulista</strong>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="estoqueDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-box-seam"></i> Estoque
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="estoque.php">
                            <i class="bi bi-eye"></i> Visualizar Estoque
                        </a></li>
                        <li><a class="dropdown-item" href="produtos.php">
                            <i class="bi bi-gear"></i> Gerenciar Produtos
                        </a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="movimentacoesDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-arrow-left-right"></i> Movimentações
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="entrada.php">
                            <i class="bi bi-arrow-down-circle"></i> Registrar Entrada
                        </a></li>
                        <li><a class="dropdown-item" href="saida.php">
                            <i class="bi bi-arrow-up-circle"></i> Registrar Saída
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="historico.php">
                            <i class="bi bi-clock-history"></i> Histórico
                        </a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="relatorio.php">
                        <i class="bi bi-file-earmark-pdf"></i> Relatórios
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>