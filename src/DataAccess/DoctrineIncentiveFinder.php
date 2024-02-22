<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;

class DoctrineIncentiveFinder implements IncentiveFinder {

	public function __construct( private readonly EntityManager $entityManager ) {
	}

	public function findIncentiveByName( string $name ): ?Incentive {
		return $this->entityManager->getRepository( Incentive::class )->findOneBy( [ 'name' => $name ] );
	}
}
