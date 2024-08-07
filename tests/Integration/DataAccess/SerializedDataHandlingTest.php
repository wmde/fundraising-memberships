<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipRepository;
use WMDE\Fundraising\MembershipContext\DataAccess\ModerationReasonRepository;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;
use WMDE\Fundraising\PaymentContext\Domain\Model\LegacyPaymentData;
use WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase;

#[CoversClass( DoctrineMembershipRepository::class )]
class SerializedDataHandlingTest extends TestCase {

	private const MEMBERSHIP_APPLICATION_ID = 715;

	/**
	 * @param array<string, mixed> $data
	 */
	#[DataProvider( 'encodedMembershipDataProvider' )]
	public function testDataFieldOfMembershipApplicationIsInteractedWithCorrectly( array $data ): void {
		$entityManager = TestEnvironment::newInstance()->getEntityManager();
		$getPaymentUseCase = $this->createStub( GetPaymentUseCase::class );
		$getPaymentUseCase->method( 'getLegacyPaymentDataObject' )->willReturn(
			new LegacyPaymentData( 100, 0, 'MCP', [] )
		);

		$repository = new DoctrineMembershipRepository(
			$entityManager,
			$getPaymentUseCase,
			new ModerationReasonRepository( $entityManager )
		);
		$this->storeMembershipApplication( $entityManager, $data );

		$membershipApplication = $repository->getUnexportedMembershipApplicationById( self::MEMBERSHIP_APPLICATION_ID );
		$this->assertNotNull( $membershipApplication );
		$repository->storeApplication( $membershipApplication );

		/** @var MembershipApplication $doctrineMembershipApplication */
		$doctrineMembershipApplication = $entityManager->find( MembershipApplication::class, self::MEMBERSHIP_APPLICATION_ID );
		$this->assertEquals( $data, $doctrineMembershipApplication->getDecodedData() );
	}

	/**
	 * @return array<int, array<int, array<string, string>>>
	 */
	public static function encodedMembershipDataProvider(): array {
		return [
			[
				[
					'member_agree' => '1',
					'membership_fee_custom' => '',
					'confirmationPage' => '10h16 Bestätigung-BEZ',
					'confirmationPageCampaign' => 'MT15_WMDE_02',
					'token' => '7998466$225c4e182d5b0f3d0e802624826200d21c900267',
					'utoken' => 'c73d8d1b3e61a73f50bd37d0bf39f3930d77f02b',
				]
			],

			[
				[
					'member_agree' => '1',
					'membership_fee_custom' => '',
					'confirmationPage' => '10h16 Bestätigung-BEZ',
					'confirmationPageCampaign' => 'MT15_WMDE_02',
					'token' => '1676175$26905b01f48c8c471f0617217540a8c82fdde52c',
					'utoken' => '87bd8cedc7843525b87f287b1e3299f667eb7a22',
				]
			],

			[
				[
					'member_agree' => '1',
					'membership_fee_custom' => '',
					'confirmationPage' => '10h16 Bestätigung',
					'confirmationPageCampaign' => 'MT15_WMDE_02',
					'token' => '3246619$c4e64cc3c49d8f8b8a0bdcf5788a029563ed9598',
					'utoken' => '134bf8fce220de781d7b093711f36b5323ccfee5',
				]
			]
		];
	}

	/**
	 * @param EntityManager $entityManager
	 * @param array<string, mixed> $data
	 *
	 * @return void
	 */
	private function storeMembershipApplication( EntityManager $entityManager, array $data ): void {
		$membershipAppl = new MembershipApplication();

		$membershipAppl->setId( self::MEMBERSHIP_APPLICATION_ID );
		$membershipAppl->setPaymentId( 1 );
		$membershipAppl->setStatus( MembershipApplication::STATUS_CONFIRMED );

		$membershipAppl->setApplicantSalutation( 'Frau' );
		$membershipAppl->setApplicantTitle( 'Dr.' );
		$membershipAppl->setApplicantFirstName( 'Martha' );
		$membershipAppl->setApplicantLastName( 'Muster' );
		$membershipAppl->setCity( 'Smalltown' );
		$membershipAppl->setPostcode( '12345' );
		$membershipAppl->setAddress( 'Erlenkamp 12' );
		$membershipAppl->setApplicantEmailAddress( 'martha.muster@mydomain.com' );

		$membershipAppl->encodeAndSetData( $data );
		$entityManager->persist( $membershipAppl );
		$entityManager->flush();
	}

}
