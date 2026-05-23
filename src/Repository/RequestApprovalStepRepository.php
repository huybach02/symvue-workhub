<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Request;
use App\Entity\RequestApprovalStep;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RequestApprovalStep>
 */
class RequestApprovalStepRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RequestApprovalStep::class);
    }

    public function findCurrentStep(Request $request): ?RequestApprovalStep
    {
        return $this->findOneBy([
            'request' => $request,
            'stepNo' => $request->getCurrentStepNo(),
            'revisionNo' => $request->getRevisionNo(),
        ]);
    }
}
