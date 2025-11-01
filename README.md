# 🧺 Controle de Estoque da Merenda Escolar

Sistema web desenvolvido para auxiliar a **coordenação e a equipe administrativa da ETEC de Bragança Paulista** no controle de **entrada e saída dos itens de merenda**.  
O projeto foi idealizado e desenvolvido **de forma independente**, com o objetivo de otimizar processos internos e reduzir falhas manuais no gerenciamento do estoque.

---

## 🚀 Funcionalidades Principais

- Cadastro, edição e exclusão de produtos  
- Registro de entradas e saídas de itens do estoque  
- Consulta rápida de quantidades disponíveis  
- Histórico de movimentações  
- Relatórios automáticos e geração de **PDFs**  
- Interface simples, intuitiva e adaptada para uso interno da escola  

---

## 🛠️ Tecnologias Utilizadas

- **PHP** — Back-end e lógica de controle de estoque  
- **MySQL** — Banco de dados para armazenamento das informações  
- **HTML5** — Estrutura das páginas  
- **CSS3** — Estilização e responsividade  
- **JavaScript** — Interatividade e validações dinâmicas  
- **Bootstrap** — Layout limpo e responsivo  
- **Composer** — Gerenciador de dependências (utilizado para biblioteca de PDF)

---

## 🗃️ Banco de Dados

O arquivo `estoque_etec.sql` está localizado na pasta `/database`.  
> Basta importá-lo no **phpMyAdmin** para criar as tabelas de produtos, movimentações e usuários do sistema.

---

## ⚙️ Instalação e Execução

1. Clone este repositório:
   ```bash
   git clone https://github.com/SEU_USUARIO/Controle-de-Estoque-ETEC.git
   
2. Acesse a pasta do projeto:
   ```bash
   cd Controle-de-Estoque-ETEC

3. Instale as dependências via Composer:
    ```bash
    composer install

4. Configure as informações de conexão com o banco de dados (ex: config.php).

5. Importe o arquivo banco_de_dados.sql no phpMyAdmin.

6. Inicie o servidor local (por exemplo, com XAMPP) e acesse:
    ```bash
    http://localhost/Controle-de-Estoque-ETEC

## 📚 Aprendizados

  * Durante o desenvolvimento deste projeto, pude aprimorar habilidades de:

  * Estruturação de sistemas de controle com PHP e MySQL

  * Organização de dados e modelagem de tabelas

  * Implementação de CRUD completo

  * Geração dinâmica de relatórios em PDF

  * Aplicação prática de autonomia e resolução de problemas reais

## 👩‍💻 Autora

**Giulia Acsa dos Santos Muniz**
Estudante do curso técnico em Desenvolvimento de Sistemas — ETEC de Bragança Paulista

📫 LinkedIn:
www.linkedin.com/in/giulia-acsa-dos-santos-muniz-b5bb13267

## ⚙️ Observação

A pasta /vendor foi ignorada no repositório por meio do .gitignore,
mas pode ser recriada automaticamente executando o comando:
   ```bash
   composer install
   ```bash
   git clone https://github.com/SEU_USUARIO/Controle-de-Estoque-ETEC.git
