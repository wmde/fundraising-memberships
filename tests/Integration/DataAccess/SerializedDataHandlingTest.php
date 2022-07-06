<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineApplicationRepository;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;
use WMDE\Fundraising\PaymentContext\Domain\Model\LegacyPaymentData;
use WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase;

/**
 * @covers \WMDE\Fundraising\MembershipContext\DataAccess\DoctrineApplicationRepository
 */
class SerializedDataHandlingTest extends TestCase {

	/** @dataProvider encodedMembershipDataProvider */
	public function testDataFieldOfMembershipApplicationIsInteractedWithCorrectly( array $data ): void {
		$entityManager = TestEnvironment::newInstance()->getEntityManager();
		$getPaymentUseCase = $this->createStub( GetPaymentUseCase::class );
		$getPaymentUseCase->method( 'getLegacyPaymentDataObject' )->willReturn(
			new LegacyPaymentData( 100, 0, 'MCP', [], 'X' )
		);

		$repository = new DoctrineApplicationRepository( $entityManager, $getPaymentUseCase );
		$this->storeMembershipApplication( $entityManager, $data );

		$membershipApplication = $repository->getApplicationById( 1 );
		$repository->storeApplication( $membershipApplication );

		/** @var MembershipApplication $doctrineMembershipApplication */
		$doctrineMembershipApplication = $entityManager->find( MembershipApplication::class, 1 );
		$this->assertEquals( $data, $doctrineMembershipApplication->getDecodedData() );
	}

	public function encodedMembershipDataProvider(): array {
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

	private function storeMembershipApplication( EntityManager $entityManager, array $data ): void {
		$membershipAppl = new MembershipApplication();
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
