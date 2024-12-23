<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\Internal\DoctrineApplicationTable;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipTracking;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipTrackingException;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipTrackingRepository;

class DoctrineMembershipTrackingRepository implements MembershipTrackingRepository {

	private DoctrineApplicationTable $table;

	public function __construct( EntityManager $entityManager ) {
		$this->table = new DoctrineApplicationTable( $entityManager );
	}

	public function storeTracking( int $membershipId, MembershipTracking $tracking ): void {
		try {
			$this->table->modifyApplication(
				$membershipId,
				static function ( MembershipApplication $application ) use ( $tracking ) {
					$application->setTracking( $tracking->getMatomoString() );
				}
			);
		} catch ( GetMembershipApplicationException | StoreMembershipApplicationException $ex ) {
			throw new MembershipTrackingException( 'Could not add tracking info', $ex );
		}
	}

	public function getTracking( int $membershipId ): MembershipTracking {
		try {
			$trackingString = $this->table->getApplicationById( $membershipId )->getTracking() ?? '';
		} catch ( GetMembershipApplicationException $e ) {
			throw new MembershipTrackingException( 'Could not find membership application', $e );
		}
		$trackingParts = explode( '/', $trackingString );
		return new MembershipTracking( $trackingParts[0], $trackingParts[1] ?? '' );
	}

}
