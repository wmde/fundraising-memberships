<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationReason;

class ModerationReasonRepository {

	public function __construct( private readonly EntityManager $entityManager ) {
	}

	/**
	 * @param ModerationReason ...$moderationReasons
	 *
	 * @return ModerationReason[]
	 */
	public function getModerationReasonsThatAreAlreadyPersisted( ModerationReason ...$moderationReasons ): array {
		if ( count( $moderationReasons ) === 0 ) {
			return [];
		}
		$queryBuilder = $this->entityManager->createQueryBuilder();
		$queryBuilder->select( 'mr' )
			->from( ModerationReason::class, 'mr' );

		$paramCounter = 1;

		foreach ( $moderationReasons as $reason ) {

			$condition1 = $queryBuilder->expr()->eq( 'mr.moderationIdentifier', "?$paramCounter" );
			$queryBuilder->setParameter( $paramCounter, $reason->getModerationIdentifier()->name );
			$paramCounter++;

			$condition2 = $queryBuilder->expr()->eq( 'mr.source', "?$paramCounter" );
			$queryBuilder->setParameter( $paramCounter, $reason->getSource() );
			$paramCounter++;

			$queryBuilder->orWhere( $queryBuilder->expr()->andX( $condition1, $condition2 ) );
		}

		$query = $queryBuilder->getQuery();

		/** @var ModerationReason[] $result */
		$result = $query->getResult();

		return $result;
	}

}
