#!/bin/bash

echo "Atualizando pacotes..."
sudo apt-get update

echo "Instalando PHP e extensões necessárias..."
sudo apt-get install -y php php-mysql php-pdo php-curl php-json php-cli

echo "Instalando MySQL Server..."
sudo apt-get install -y mysql-server

echo "Iniciando serviço MySQL..."
sudo service mysql start

echo "Configurando banco de dados e usuário MySQL..."
sudo mysql -e "CREATE DATABASE IF NOT EXISTS raffle_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY '';"
sudo mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

echo "Importando esquema do banco de dados..."
mysql -u root raffle_system < db.sql

echo "Configuração concluída. Você pode iniciar o servidor PHP com:"
echo "php -S localhost:8000"
