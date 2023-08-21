<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\Internal\DoctrineApplicationTable;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Tracking\ApplicationTracker;
use WMDE\Fundraising\MembershipContext\Tracking\ApplicationTrackingException;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipApplicationTrackingInfo;

class DoctrineApplicationTracker implements ApplicationTracker {

	private DoctrineApplicationTable $table;

	public function __construct( EntityManager $entityManager ) {
		$this->table = new DoctrineApplicationTable( $entityManager );
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
		} catch ( GetMembershipApplicationException | StoreMembershipApplicationException $ex ) {
			throw new ApplicationTrackingException( 'Failed to track membership application', $ex );
		}
	}

}
