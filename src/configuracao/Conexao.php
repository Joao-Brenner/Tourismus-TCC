<?php
namespace PADS\App\configuracao;

use PDO;
use PDOException;

abstract class Conexao {
    private static $jaLogou = false;

    public static function conectar() {
        $host   = $_ENV['DB_HOST'];
        $dbname = $_ENV['DB_NAME'];
        $user   = $_ENV['DB_USER'];
        $pass   = $_ENV['DB_PASS'];

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

        $opcoes = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        try {
            $pdo = new PDO(
                $dsn,
                $user,
                $pass,
                $opcoes
            );

            if (!self::$jaLogou) {
                error_log('Sucesso na conexao com o banco de dados.');
                self::$jaLogou = true;
            }

            return $pdo;
        } catch (PDOException $e) {
            error_log('Erro na conexao com o banco de dados: ' . addslashes($e->getMessage()));
            return null;
        }
    }
}
?>
