<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication as DoctrineApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipRepository;
use WMDE\Fundraising\MembershipContext\DataAccess\ModerationReasonRepository;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationIdentifier;
use WMDE\Fundraising\MembershipContext\Domain\Model\ModerationReason;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationAnonymizedException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\MembershipRepository;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidPayments;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\ThrowingEntityManagerTrait;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;
use WMDE\Fundraising\PaymentContext\UseCases\GetPayment\GetPaymentUseCase;

#[CoversClass( DoctrineMembershipRepository::class )]
class DoctrineMembershipApplicationRepositoryTest extends TestCase {

	use ThrowingEntityManagerTrait;

	private const int MEMBERSHIP_APPLICATION_ID = 1;
	private const int ID_OF_APPLICATION_NOT_IN_DB = 35505;

	private EntityManager $entityManager;

	public function setUp(): void {
		$testEnvironment = TestEnvironment::newInstance();
		$this->entityManager = $testEnvironment->getEntityManager();
		parent::setUp();
	}

	public function testValidMembershipApplicationGetPersisted(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$this->givenApplicationRepository()->storeApplication( $application );

		$expected = ValidMembershipApplication::newDoctrineEntity();
		$expected->setId( $application->getId() );

		$actual = $this->getApplicationFromDatabase( $application->getId() );
		// Sync the creation dates as they get set to now()
		$actual->setCreationTime( $expected->getCreationTime() );
		// Override values that get created on persistence
		$actual->setData( null );
		$actual->setCompany( null );
		$actual->setIncentives( new ArrayCollection() );

		// reset the moderation reasons because doctrine sets the moderation reasons to a PersistedCollection instead of ArrayCollection
		// this way we can compare the objects
		$actual->setModerationReasons( ...$expected->getModerationReasons()->toArray() );
		$this->assertEquals( $expected, $actual );
	}

	public function testStoringAMembershipApplicationCreatesAndAssignsId(): void {
		$application = ValidMembershipApplication::newDomainEntity();

		$this->givenApplicationRepository()->storeApplication( $application );

		$this->assertSame( self::MEMBERSHIP_APPLICATION_ID, $application->getId() );
	}

	public function testWriteAndReadRoundtrip(): void {
		$repository = $this->givenApplicationRepository();
		$application = ValidMembershipApplication::newDomainEntity();

		$repository->storeApplication( $application );

		$this->assertEquals(
			$application,
			$repository->getMembershipApplicationById( self::MEMBERSHIP_APPLICATION_ID )
		);
	}

	public function testApplicationWithIncentivesHasIncentivesAfterRoundtrip(): void {
		$incentive = ValidMembershipApplication::newIncentive();
		$this->entityManager->persist( $incentive );
		$this->entityManager->flush();
		$application = ValidMembershipApplication::newCompanyApplication();
		$application->addIncentive( $incentive );
		$repo = $this->givenApplicationRepository();
		$repo->storeApplication( $application );
		// find() will retrieve a cached value, so we should clear the entity cache here
		$this->entityManager->clear();

		$actual = $repo->getMembershipApplicationById( $application->getId() );
		$this->assertNotNull( $actual );
		$incentives = iterator_to_array( $actual->getIncentives() );

		$this->assertCount( 1, $incentives );
		$this->assertEquals( $incentive, $incentives[0] );
	}

	public function testNewModeratedMembershipApplicationPersistenceRoundTrip(): void {
		$application = ValidMembershipApplication::newCompanyApplication();
		$application->markForModeration(
			new ModerationReason( ModerationIdentifier::ADDRESS_CONTENT_VIOLATION )
		);

		$repository = $this->givenApplicationRepository();

		$repository->storeApplication( $application );
		// find() will retrieve a cached value, so we should clear the entity cache here
		$this->entityManager->clear();
		$membershipApplication = $repository->getMembershipApplicationById( $application->getId() );

		$this->assertNotNull( $membershipApplication );
		$this->assertEquals( $application->getModerationReasons(), $membershipApplication->getModerationReasons() );
	}

	public function testGetMembershipApplicationById_WhenMembershipApplicationInDatabase_itIsReturnedAsMatchingDomainEntity(): void {
		$this->storeDoctrineApplication( ValidMembershipApplication::newDoctrineEntity() );

		$expected = ValidMembershipApplication::newDomainEntity( self::MEMBERSHIP_APPLICATION_ID );

		$this->assertEquals(
			$expected,
			$this->givenApplicationRepository()->getMembershipApplicationById( self::MEMBERSHIP_APPLICATION_ID )
		);
	}

	public function testGetMembershipApplicationById_WhenEntityDoesNotExist_getEntityReturnsNull(): void {
		$this->assertNull( $this->givenApplicationRepository()->getMembershipApplicationById( self::ID_OF_APPLICATION_NOT_IN_DB ) );
	}

	public function testGetMembershipApplicationById_WhenReadFails_domainExceptionIsThrown(): void {
		$repository = $this->givenApplicationRepository( $this->getThrowingEntityManager() );

		$this->expectException( GetMembershipApplicationException::class );
		$repository->getMembershipApplicationById( self::ID_OF_APPLICATION_NOT_IN_DB );
	}

	public function testGetMembershipApplicationById_ReadingAnonymizedApplication_itIsReturnedAsMatchingDomainEntity(): void {
		$this->storeDoctrineApplication( ValidMembershipApplication::newAnonymizedDoctrineEntity() );

		$expected = ValidMembershipApplication::newDomainEntity( self::MEMBERSHIP_APPLICATION_ID );

		$this->assertEquals(
			$expected,
			$this->givenApplicationRepository()->getMembershipApplicationById( self::MEMBERSHIP_APPLICATION_ID )
		);
	}

	public function testGetUnexportedMembershipApplicationById_WhenMembershipApplicationInDatabase_itIsReturnedAsMatchingDomainEntity(): void {
		$this->storeDoctrineApplication( ValidMembershipApplication::newDoctrineEntity() );

		$expected = ValidMembershipApplication::newDomainEntity( self::MEMBERSHIP_APPLICATION_ID );

		$this->assertEquals(
			$expected,
			$this->givenApplicationRepository()->getUnexportedMembershipApplicationById( self::MEMBERSHIP_APPLICATION_ID )
		);
	}

	public function testGetUnexportedMembershipApplicationById_WhenEntityDoesNotExist_getEntityReturnsNull(): void {
		$this->assertNull( $this->givenApplicationRepository()->getUnexportedMembershipApplicationById( self::ID_OF_APPLICATION_NOT_IN_DB ) );
	}

	public function testGetUnexportedMembershipApplicationById_WhenReadFails_domainExceptionIsThrown(): void {
		$repository = $this->givenApplicationRepository( $this->getThrowingEntityManager() );

		$this->expectException( GetMembershipApplicationException::class );
		$repository->getUnexportedMembershipApplicationById( self::ID_OF_APPLICATION_NOT_IN_DB );
	}

	public function testGetUnexportedMembershipApplicationById_ReadingAnonymizedApplication_anonymizedExceptionIsThrown(): void {
		$this->storeDoctrineApplication( ValidMembershipApplication::newAnonymizedDoctrineEntity() );

		$this->expectException( ApplicationAnonymizedException::class );

		$this->givenApplicationRepository()->getUnexportedMembershipApplicationById( self::MEMBERSHIP_APPLICATION_ID );
	}

	public function testWhenApplicationAlreadyExists_persistingCausesUpdate(): void {
		$repository = $this->givenApplicationRepository();
		$originalApplication = ValidMembershipApplication::newDomainEntity();

		$repository->storeApplication( $originalApplication );

		// It is important a new instance is created here to test "detached entity" handling
		$newApplication = ValidMembershipApplication::newDomainEntity( $originalApplication->getId() );
		$newApplication->getApplicant()->changeEmailAddress( new EmailAddress( 'chuck.norris@always.win' ) );

		$repository->storeApplication( $newApplication );

		$doctrineApplication = $this->getApplicationFromDatabase( $newApplication->getId() );

		$this->assertSame( 'chuck.norris@always.win', $doctrineApplication->getApplicantEmailAddress() );
	}

	public function testGivenCompanyApplication_companyNameIsPersisted(): void {
		$this->givenApplicationRepository()->storeApplication( ValidMembershipApplication::newCompanyApplication() );

		$expected = ValidMembershipApplication::newDoctrineCompanyEntity();
		$expected->setId( self::MEMBERSHIP_APPLICATION_ID );

		$actual = $this->getApplicationFromDatabase( self::MEMBERSHIP_APPLICATION_ID );

		$this->assertNotNull( $actual->getCompany() );
		$this->assertEquals( $expected->getCompany(), $actual->getCompany() );
	}

	private function givenApplicationRepository( ?EntityManager $entityManager = null ): MembershipRepository {
		return new DoctrineMembershipRepository(
			$entityManager ?? $this->entityManager,
			$this->givenGetPaymentUseCaseStub(),
			new ModerationReasonRepository( $this->entityManager )
		);
	}

	private function givenGetPaymentUseCaseStub(): GetPaymentUseCase {
		$stub = $this->createStub( GetPaymentUseCase::class );
		$stub->method( 'getLegacyPaymentDataObject' )->willReturn( ValidPayments::newDirectDebitLegacyData() );
		return $stub;
	}

	private function getApplicationFromDatabase( int $id ): DoctrineApplication {
		$applicationRepo = $this->entityManager->getRepository( DoctrineApplication::class );
		$donation = $applicationRepo->find( $id );
		$this->assertInstanceOf( DoctrineApplication::class, $donation );
		return $donation;
	}

	private function storeDoctrineApplication( DoctrineApplication $application ): void {
		$this->entityManager->persist( $application );
		$this->entityManager->flush();
	}

}
