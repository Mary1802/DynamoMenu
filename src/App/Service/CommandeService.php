<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Application;
use App\Model\CommandeLine;
use App\Model\CommandeStatut;
use App\Repository\CommandeRepository;

final class CommandeService
{
    public function __construct(
        private readonly CommandeRepository $repository,
        private readonly ActivityLogService $activityLog,
    ) {
    }

    public static function fromApp(?Application $app = null): self
    {
        $app ??= Application::getInstance();

        return new self(
            new CommandeRepository($app->db()),
            $app->activityLog()
        );
    }

    /** @return array{en_attente:int,en_preparation:int,prete:int} */
    public function kitchenStats(): array
    {
        return [
            'en_attente' => $this->repository->countByStatut(CommandeStatut::EN_ATTENTE),
            'en_preparation' => $this->repository->countByStatut(CommandeStatut::EN_PREPARATION),
            'prete' => $this->repository->countByStatut(CommandeStatut::PRETE),
        ];
    }

    public function startPreparation(int $numCommande): void
    {
        $this->repository->updateStatut($numCommande, CommandeStatut::EN_PREPARATION);
    }

    public function markReady(int $numCommande): void
    {
        $this->repository->updateStatut($numCommande, CommandeStatut::PRETE);
        Application::getInstance()->notificationService()->notifyCommandePrete($numCommande);
    }

    public function markDelivered(int $numCommande): void
    {
        $this->repository->markDelivered($numCommande);
    }

    public function updateStatut(int $numCommande, string $statut, string $logModule = 'admin'): void
    {
        if (!CommandeStatut::isValid($statut)) {
            return;
        }

        $this->repository->updateStatut($numCommande, $statut);
        $this->activityLog->log('commande_statut', "Commande #{$numCommande} → {$statut}", $logModule);

        if ($statut === CommandeStatut::PRETE) {
            Application::getInstance()->notificationService()->notifyCommandePrete($numCommande);
        }
    }

    public function handleKitchenAction(string $action, int $numCommande): void
    {
        match ($action) {
            'en_cours' => $this->startPreparation($numCommande),
            'termine' => $this->markReady($numCommande),
            'livree' => $this->markDelivered($numCommande),
            default => null,
        };
    }

    /**
     * @param list<array<string, mixed>> $orders
     */
    public function attachLines(array &$orders): void
    {
        foreach ($orders as &$order) {
            $num = (int) ($order['num_commande'] ?? 0);
            if ($num <= 0) {
                $order['lignes'] = [];
                continue;
            }

            $lines = $this->repository->fetchLines($num);
            $order['lignes'] = array_map(static fn(CommandeLine $l): array => $l->toArray(), $lines);
            $order['details_search'] = implode(' ', array_map(
                static fn(CommandeLine $l): string => $l->label(),
                $lines
            ));
        }
        unset($order);
    }

    public function lineLabel(array $line): string
    {
        return CommandeLine::fromRow($line)->label();
    }

    public function repository(): CommandeRepository
    {
        return $this->repository;
    }
}
