<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use Psr\Log\NullLogger;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\Internal\DoctrineApplicationTable;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Tracking\ApplicationPiwikTracker;
use WMDE\Fundraising\MembershipContext\Tracking\ApplicationPiwikTrackingException;

class DoctrineApplicationPiwikTracker implements ApplicationPiwikTracker {

	private DoctrineApplicationTable $table;

	public function __construct( EntityManager $entityManager ) {
		// TODO: Add non-null logger
		$this->table = new DoctrineApplicationTable( $entityManager, new NullLogger() );
	}

	public function trackApplication( int $applicationId, string $trackingString ): void {
		try {
			$this->table->modifyApplication(
				$applicationId,
				static function ( MembershipApplication $application ) use ( $trackingString ) {
					$application->setTracking( $trackingString );
				}
			);
		} catch ( GetMembershipApplicationException | StoreMembershipApplicationException $ex ) {
			throw new ApplicationPiwikTrackingException( 'Could not add tracking info', $ex );
		}
	}

}
