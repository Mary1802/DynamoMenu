<?php

class DetailCommande
{
    public int $commandeId;
    public int $platId;
    public int $quantity;

    public function __construct(int $commandeId, int $platId, int $quantity)
    {
        $this->commandeId = $commandeId;
        $this->platId = $platId;
        $this->quantity = $quantity;
    }
}

