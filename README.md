# Sistema de Rifas com Integração PIX

Sistema completo de rifas online com integração PIX através da API BlackCat Pagamentos.

## Características

- Interface moderna e responsiva usando Tailwind CSS
- Integração PIX para pagamentos automáticos
- Painel administrativo completo
- Gerenciamento de campanhas e sorteios
- Sistema de combos de números
- Confirmação automática de pagamentos
- Relatórios de vendas e transações

## Requisitos do Sistema

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Extensões PHP:
  - PDO
  - MySQLi
  - cURL
  - JSON

## Instalação

1. Clone o repositório:
```bash
git clone [url-do-repositorio]
cd [pasta-do-projeto]
```

2. Configure o banco de dados:
- Crie um banco de dados MySQL
- Importe o arquivo `db.sql`
```bash
mysql -u seu_usuario -p seu_banco < db.sql
```

3. Configure as credenciais:
- Copie `config.php` e ajuste as configurações:
  - Credenciais do banco de dados
  - Chaves da API BlackCat:
    - Secret Key: sk_Br-pkbauum5bAzSRqqHa1kfcirDqVLrVMRu5Dr-gZdn2B4WP
    - Public Key: pk_pXb05DCxytcnz8SViYmOjSo2BlHKf0vUlpegTgmgkfwdNF-7

4. Configure as permissões:
```bash
chmod 755 -R ./
chmod 777 -R ./logs
```

## Estrutura de Diretórios

```
/
├── admin/              # Painel administrativo
├── api/               # Integrações (BlackCat)
├── assets/            # Recursos estáticos
│   ├── images/       # Imagens
│   └── logos/        # Logos
├── css/              # Estilos CSS
├── js/               # Scripts JavaScript
├── logs/             # Logs do sistema
├── config.php        # Configurações
├── db.sql            # Esquema do banco
└── README.md         # Este arquivo
```

## Configuração do Ambiente de Desenvolvimento

### Localhost (XAMPP/WAMP)

1. Instale XAMPP ou WAMP
2. Clone o projeto na pasta `htdocs` ou `www`
3. Configure o Virtual Host (opcional):
```apache
<VirtualHost *:80>
    DocumentRoot "/path/to/project"
    ServerName rifas.local
    <Directory "/path/to/project">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Produção (Hostinger/Railway)

#### Hostinger
1. Faça upload dos arquivos via FTP
2. Configure o banco de dados no painel
3. Ajuste config.php com as credenciais

#### Railway
1. Configure as variáveis de ambiente
2. Use o buildpack PHP
3. Configure o banco de dados MySQL

## Integração PIX (BlackCat)

A integração PIX utiliza a API da BlackCat Pagamentos. O fluxo é:

1. Cliente seleciona números
2. Sistema gera cobrança PIX
3. Cliente realiza pagamento
4. Webhook confirma pagamento
5. Sistema libera números

### Configuração do PIX

1. Verifique as chaves em `config.php`:
```php
define('BLACKCAT_SECRET_KEY', 'sk_Br-pkbauum5bAzSRqqHa1kfcirDqVLrVMRu5Dr-gZdn2B4WP');
define('BLACKCAT_PUBLIC_KEY', 'pk_pXb05DCxytcnz8SViYmOjSo2BlHKf0vUlpegTgmgkfwdNF-7');
```

2. Teste a integração:
```bash
php -f tests/pix_test.php
```

## Painel Administrativo

Acesse: `http://seu-dominio/admin`

Credenciais padrão:
- Usuário: admin
- Senha: admin123

**Importante:** Altere a senha no primeiro acesso!

## Personalização

### Logos e Imagens
- Substitua arquivos em `/assets/logos/`
- Use imagens otimizadas (max 2MB)

### Cores e Estilos
- Edite `css/main.css`
- Ou use classes Tailwind

### Textos
- Edite diretamente nos arquivos PHP
- Ou crie arquivo de idiomas

## Manutenção

### Logs
- Verifique `/logs/error.log`
- Monitore `/logs/transactions.log`

### Backup
```bash
# Banco de dados
mysqldump -u user -p database > backup.sql

# Arquivos
tar -czf backup.tar.gz ./
```

## Segurança

- Mantenha PHP atualizado
- Use HTTPS
- Altere senhas regularmente
- Monitore logs
- Faça backups

## Suporte

Para suporte técnico:
1. Verifique logs
2. Consulte documentação
3. Abra issue no GitHub

## Licença

Este projeto está sob a licença MIT. Veja LICENSE para detalhes.
