<?php
// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'estoque_etec');

// Configurações da Aplicação
define('SITE_NAME', 'ETEC Bragança Paulista');
define('SITE_SUBTITLE', 'Sistema de Controle de Estoque');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Conexão com o banco de dados
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Erro na conexão: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// Função auxiliar para formatar data BR
function formatarData($data) {
    if (empty($data)) return '';
    $dt = new DateTime($data);
    return $dt->format('d/m/Y');
}

// Função auxiliar para formatar data para banco
function formatarDataBanco($data) {
    if (empty($data)) return null;
    $dt = DateTime::createFromFormat('d/m/Y', $data);
    return $dt ? $dt->format('Y-m-d') : null;
}

// Função para formatar número
function formatarNumero($numero, $decimais = 2) {
    return number_format($numero, $decimais, ',', '.');
}

// Iniciar sessão
session_start();
?>