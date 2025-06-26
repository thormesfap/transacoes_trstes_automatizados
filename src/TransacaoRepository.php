<?php

namespace App;

use PDO;
use DateTime;

class TransacaoRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function inserir($id, $valor, $dataHora)
    {
        $stmt = $this->pdo->prepare('INSERT INTO transacoes (id, valor, dataHora) VALUES (?, ?, ?)');
        return $stmt->execute([$id, $valor, $dataHora]);
    }

    public function buscarPorId($id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM transacoes WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deletarPorId($id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM transacoes WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function deletarTodas()
    {
        return $this->pdo->exec('DELETE FROM transacoes');
    }

    public function estatisticas(int $seconds)
    {
        $agora = (new DateTime())->format(DateTime::ATOM);
        $limite = (new DateTime("-$seconds seconds"))->format(DateTime::ATOM);
        $stmt = $this->pdo->prepare('SELECT valor FROM transacoes WHERE dataHora BETWEEN ? AND ?');
        $stmt->execute([$limite, $agora]);
        $valores = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $count = count($valores);
        $sum = $count ? array_sum($valores) : 0.0;
        $avg = $count ? $sum / $count : 0.0;
        $min = $count ? min($valores) : 0.0;
        $max = $count ? max($valores) : 0.0;
        return [
            'count' => $count,
            'sum' => (float)$sum,
            'avg' => (float)$avg,
            'min' => (float)$min,
            'max' => (float)$max
        ];
    }
}
