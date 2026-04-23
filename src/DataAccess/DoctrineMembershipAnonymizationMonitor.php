<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\DBAL\Connection;
use WMDE\Clock\Clock;
use WMDE\Fundraising\MembershipContext\Domain\MembershipAnonymizationMonitor;

class DoctrineMembershipAnonymizationMonitor implements MembershipAnonymizationMonitor {

	private const string MODERATION_GRACE_PERIOD = 'P1M';

	private Connection $conn;
	private Clock $clock;

	public function __construct( Connection $conn, Clock $clock ) {
		$this->conn = $conn;
		$this->clock = $clock;
	}

	public function countOldAbandonedModeratedMembershipApplications(): int {
		$now = $this->clock->now();
		$gracePeriodDate = \DateTime::createFromImmutable( $now->sub( new \DateInterval( self::MODERATION_GRACE_PERIOD ) ) );

		$sqlQuery = "SELECT COUNT(id) as count FROM request r INNER JOIN memberships_moderation_reasons mmr ON r.id=mmr.membership_id " .
			"WHERE ( ( r.name is not null AND r.name!='' ) OR ( r.email is not NULL AND r.email!='' ) ) AND r.timestamp < :gracePeriodDate;";
		$queryResult = $this->conn->executeQuery(
			sql: $sqlQuery,
			params: [ 'gracePeriodDate' => $gracePeriodDate->format( 'Y-m-d H:i:s' ) ]
		);

		$count = $queryResult->fetchOne();

		if ( !is_scalar( $count ) ) {
			return -1;
		}
		return intval( $count );
	}

}
