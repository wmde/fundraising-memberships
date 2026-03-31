<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\DBAL\Connection;
use WMDE\Fundraising\MembershipContext\Domain\MembershipAnonymizationMonitor;

class DoctrineMembershipAnonymizationMonitor implements MembershipAnonymizationMonitor {

	private const string MODERATION_GRACE_PERIOD = 'P1M';

	private Connection $conn;

	public function __construct( Connection $conn ) {
		$this->conn = $conn;
	}

	public function countOldAbandonedModeratedMembershipApplications(): int {
		$queryBuilder = $this->conn->createQueryBuilder();

		$now = new \DateTimeImmutable();
		$gracePeriodDate = \DateTime::createFromImmutable( $now->sub( new \DateInterval( self::MODERATION_GRACE_PERIOD ) ) );

		$queryBuilder
			->select( 'id' )
			->from( 'request', 'r' )
			// only look for membership applications that got flagged for moderation
			/*->innerJoin(
				fromAlias: 'r',
				join: 'memberships_moderation_reasons',
				alias: 'mmr'
			)*/

			// only include them if they still contain personal data
		/*->where(
				$queryBuilder->expr()->or(
					$queryBuilder->expr()->and(
						$queryBuilder->expr()->isNotNull( 'r.name' ),
						$queryBuilder->expr()->neq( 'r.name', '' )
					),
					$queryBuilder->expr()->and(
						$queryBuilder->expr()->isNotNull( 'r.email' ),
						$queryBuilder->expr()->neq( 'r.email', '' )
					)
			) ); */

			->where(
					$queryBuilder->expr()->and(
						$queryBuilder->expr()->isNotNull( 'r.name' ),
						$queryBuilder->expr()->neq( 'r.name', '' )
					)
				);
			// only look for older "seemingly abandoned" entries older than the grace period
			//->andWhere( 'r.timestamp <= :moderationGracePeriodDate' );
			//->setParameter( 'moderationGracePeriodDate', $gracePeriodDate );

		$count = $queryBuilder->executeQuery()->rowCount();

		if ( !is_scalar( $count ) ) {
			return -1;
		}
		return intval( $count );
	}

}
