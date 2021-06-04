<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use Psr\Log\NullLogger;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\Internal\DoctrineApplicationTable;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Tracking\ApplicationTracker;
use WMDE\Fundraising\MembershipContext\Tracking\ApplicationTrackingException;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipApplicationTrackingInfo;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class DoctrineApplicationTracker implements ApplicationTracker {

	private $table;

	public function __construct( EntityManager $entityManager ) {
		// TODO: Add non-null logger
		$this->table = new DoctrineApplicationTable( $entityManager, new NullLogger() );
	}

	public function trackApplication( int $applicationId, MembershipApplicationTrackingInfo $trackingInfo ): void {
		try {
			$this->table->modifyApplication(
				$applicationId,
				static function ( MembershipApplication $application ) use ( $trackingInfo ) {
					$data = $application->getDecodedData();
					$data['confirmationPageCampaign'] = $trackingInfo->getCampaignCode();
					$data['confirmationPage'] = $trackingInfo->getKeyword();
					$application->encodeAndSetData( $data );
				}
			);
		}
		catch ( GetMembershipApplicationException | StoreMembershipApplicationException $ex ) {
			throw new ApplicationTrackingException( 'Failed to track membership application', $ex );
		}
	}

}
