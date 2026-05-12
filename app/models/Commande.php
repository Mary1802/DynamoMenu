<?php

class Commande
{
    public int $id;
    public int $userId;
    public float $total;
    public string $status;

    public function __construct(int $userId, float $total)
    {
        $this->userId = $userId;
        $this->total = $total;
        $this->status = 'en attente';
    }
}

