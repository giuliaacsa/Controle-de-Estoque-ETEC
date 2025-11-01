// Buscar dados para os gráficos
async function carregarDadosGraficos() {
    try {
        const response = await fetch('api/graficos_data.php');
        const data = await response.json();
        
        // Gráfico de Estoque por Categoria
        criarGraficoCategoria(data.categorias);
        
        // Gráfico de Status dos Produtos
        criarGraficoStatus(data.status);
        
    } catch (error) {
        console.error('Erro ao carregar dados dos gráficos:', error);
    }
}

// Gráfico de Estoque por Categoria (Barras)
function criarGraficoCategoria(dados) {
    const ctx = document.getElementById('chartCategoria');
    if (!ctx) return;
    
    const cores = [
        'rgba(196, 30, 58, 0.8)',
        'rgba(139, 0, 0, 0.8)',
        'rgba(165, 42, 42, 0.8)',
        'rgba(220, 20, 60, 0.8)',
        'rgba(178, 34, 34, 0.8)',
        'rgba(205, 92, 92, 0.8)',
        'rgba(128, 0, 0, 0.8)'
    ];
    
    const coresBorda = [
        'rgba(196, 30, 58, 1)',
        'rgba(139, 0, 0, 1)',
        'rgba(165, 42, 42, 1)',
        'rgba(220, 20, 60, 1)',
        'rgba(178, 34, 34, 1)',
        'rgba(205, 92, 92, 1)',
        'rgba(128, 0, 0, 1)'
    ];
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dados.map(d => d.categoria),
            datasets: [{
                label: 'Pacotes em Estoque',
                data: dados.map(d => d.total_pacotes),
                backgroundColor: cores,
                borderColor: coresBorda,
                borderWidth: 2
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
                            weight: 'bold'
                        },
                        color: '#1a1a1a'
                    }
                },
                title: {
                    display: false
                },
                tooltip: {
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

// Gráfico de Status dos Produtos (Pizza)
function criarGraficoStatus(dados) {
    const ctx = document.getElementById('chartStatus');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Normal', 'Estoque Baixo', 'Crítico'],
            datasets: [{
                data: [
                    dados.normal || 0,
                    dados.baixo || 0,
                    dados.critico || 0
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
                            size: 12,
                            weight: 'bold'
                        },
                        padding: 15,
                        color: '#1a1a1a'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const value = context.parsed;
                            const percentage = ((value / total) * 100).toFixed(1);
                            return context.label + ': ' + value + ' produtos (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}

// Carregar gráficos quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    carregarDadosGraficos();
});