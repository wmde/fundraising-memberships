<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\Internal\DoctrineApplicationTable;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipTrackingException;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipTrackingRepository;

class DoctrineMembershipTrackingRepository implements MembershipTrackingRepository {

	private DoctrineApplicationTable $table;

	public function __construct( EntityManager $entityManager ) {
		$this->table = new DoctrineApplicationTable( $entityManager );
	}

	public function storeTracking( int $membershipId, string $trackingString ): void {
		try {
			$this->table->modifyApplication(
				$membershipId,
				static function ( MembershipApplication $application ) use ( $trackingString ) {
					$application->setTracking( $trackingString );
				}
			);
		} catch ( GetMembershipApplicationException | StoreMembershipApplicationException $ex ) {
			throw new MembershipTrackingException( 'Could not add tracking info', $ex );
		}
	}

	public function getTracking( int $membershipId ): string {
		try {
			return $this->table->getApplicationById( $membershipId )->getTracking() ?? '';
		} catch ( GetMembershipApplicationException $e ) {
			throw new MembershipTrackingException( 'Could not find membership application', $e );
		}
	}

}
