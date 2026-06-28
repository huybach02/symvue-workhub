<?php

declare(strict_types=1);

namespace App\Service;

use App\Class\FilterWithPagination;
use App\DTO\ProviderDTO;
use App\Entity\Provider;
use App\Repository\ProviderRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProviderService
{
    public function __construct(
        private readonly ProviderRepository $providerRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findAll(array $params): array
    {
        $qb = $this->providerRepository->createQueryBuilder('e');

        $result = FilterWithPagination::findWithPagination($qb, $params, 'e');

        // Map collection to JSON
        $result['collection'] = array_map(
            fn(Provider $item) => $item->jsonSerialize(),
            $result['collection']
        );

        return $result;
    }

    public function findById(int $id): array
    {
        $item = $this->providerRepository->find($id);
        
        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        return $item->jsonSerialize();
    }

    public function create(ProviderDTO $dto): array
    {
        $item = new Provider();
        
        $item->setCode($dto->code);
        $item->setName($dto->name);
        $item->setPhone($dto->phone);
        $item->setEmail($dto->email);
        $item->setAddress($dto->address);
        $item->setTaxNumber($dto->taxNumber);
        $item->setBankName($dto->bankName);
        $item->setBankNumber($dto->bankNumber);
        $item->setNote($dto->note);
        $item->setStatus($dto->status ?? 1);
        
        $this->entityManager->persist($item);
        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function update(int $id, ProviderDTO $dto): array
    {
        $item = $this->providerRepository->find($id);
        
        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $item->setCode($dto->code);
        $item->setName($dto->name);
        $item->setPhone($dto->phone);
        $item->setEmail($dto->email);
        $item->setAddress($dto->address);
        $item->setTaxNumber($dto->taxNumber);
        $item->setBankName($dto->bankName);
        $item->setBankNumber($dto->bankNumber);
        $item->setNote($dto->note);
        $item->setStatus($dto->status ?? 1);

        $this->entityManager->flush();

        return $item->jsonSerialize();
    }

    public function delete(int $id): void
    {
        $item = $this->providerRepository->find($id);
        
        if (!$item) {
            throw new \Exception(t('error.not_found'));
        }

        $this->entityManager->remove($item);
        $this->entityManager->flush();
    }

    public function getDataSelect(): array
    {
        $providers = $this->providerRepository->findBy(['status' => 1]);

        return array_map(fn(Provider $provider) => [
            'label' => $provider->getName(),
            'value' => $provider->getId()
        ], $providers);
    }
}
