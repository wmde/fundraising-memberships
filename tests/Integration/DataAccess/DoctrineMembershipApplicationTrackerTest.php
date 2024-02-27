<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineApplicationTracker;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;
use WMDE\Fundraising\MembershipContext\Tracking\ApplicationTracker;
use WMDE\Fundraising\MembershipContext\Tracking\MembershipApplicationTrackingInfo;

/**
 * @covers \WMDE\Fundraising\MembershipContext\DataAccess\DoctrineApplicationTracker
 *
 * @license GPL-2.0-or-later
 */
class DoctrineMembershipApplicationTrackerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var EntityManager
	 */
	private $entityManager;

	public function setUp(): void {
		$this->entityManager = TestEnvironment::newInstance()->getEntityManager();
	}

	/**
	 * @dataProvider validTrackingDataProvider
	 */
	public function testValidTrackingDataIsProperlyApplied( string $campaignCode, string $keyword ): void {
		$application = ValidMembershipApplication::newDoctrineEntity();
		$this->persistApplication( $application );

		$this->newMembershipApplicationTracker()->trackApplication(
			$application->getId(),
			$this->newMembershipApplicationTrackingInfo( $campaignCode, $keyword )
		);

		$storedApplication = $this->getApplicationById( $application->getId() );

		$this->assertSame( $keyword, $storedApplication->getDecodedData()['confirmationPage'] );
		$this->assertSame( $campaignCode, $storedApplication->getDecodedData()['confirmationPageCampaign'] );
	}

	public static function validTrackingDataProvider(): array {
		return [
			[ 'campaignCode', 'keyword' ],
			[ '', 'keyword', 'keyword' ],
			[ 'campaignCode', '' ],
			[ '', '' ],
		];
	}

	private function persistApplication( MembershipApplication $application ): void {
		$this->entityManager->persist( $application );
		$this->entityManager->flush();
	}

	private function getApplicationById( int $applicationId ): MembershipApplication {
		return $this->entityManager->find( MembershipApplication::class, $applicationId );
	}

	private function newMembershipApplicationTracker(): ApplicationTracker {
		return new DoctrineApplicationTracker( $this->entityManager );
	}

	private function newMembershipApplicationTrackingInfo( string $campaignCode, string $keyword ): MembershipApplicationTrackingInfo {
		return new MembershipApplicationTrackingInfo( $campaignCode, $keyword );
	}

}
