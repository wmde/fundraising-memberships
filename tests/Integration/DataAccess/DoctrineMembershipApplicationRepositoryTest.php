<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Doctrine\ORM\EntityManager;
use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication as DoctrineApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineApplicationRepository;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationAnonymizedException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FixedMembershipTokenGenerator;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ThrowingEntityManager;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;

/**
 * @covers \WMDE\Fundraising\MembershipContext\DataAccess\DoctrineApplicationRepository
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DoctrineMembershipApplicationRepositoryTest extends \PHPUnit\Framework\TestCase {

	private const MEMBERSHIP_APPLICATION_ID = 1;
	private const ID_OF_APPLICATION_NOT_IN_DB = 35505;
	private const VALID_TOKEN = 'access_token';
	private const FUTURE_EXPIRY = '3000-01-01 0:00:00';

	/**
	 * @var EntityManager
	 */
	private $entityManager;

	public function setUp(): void {
		$testEnvironment = TestEnvironment::newInstance();
		$testEnvironment->setTokenGenerator( new FixedMembershipTokenGenerator( self::VALID_TOKEN, new \DateTime( self::FUTURE_EXPIRY ) ) );
		$this->entityManager = $testEnvironment->getEntityManager();
		parent::setUp();
	}

	public function testValidMembershipApplicationGetPersisted(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$this->newRepository()->storeApplication( $application );

		$expected = ValidMembershipApplication::newDoctrineEntity();
		$expected->setId( $application->getId() );

		$actual = $this->getApplicationFromDatabase( $application->getId() );
		$actual->setCreationTime( null );
		$actual->setData( null );
		$actual->setCompany( null );

		$this->assertEquals( $expected, $actual );
	}

	private function newRepository(): ApplicationRepository {
		return new DoctrineApplicationRepository( $this->entityManager );
	}

	private function getApplicationFromDatabase( int $id ): DoctrineApplication {
		$applicationRepo = $this->entityManager->getRepository( DoctrineApplication::class );
		$donation = $applicationRepo->find( $id );
		$this->assertInstanceOf( DoctrineApplication::class, $donation );
		return $donation;
	}

	public function testIdGetsAssigned(): void {
		$application = ValidMembershipApplication::newDomainEntity();

		$this->newRepository()->storeApplication( $application );

		$this->assertSame( self::MEMBERSHIP_APPLICATION_ID, $application->getId() );
	}

	public function testWhenPersistenceFails_domainExceptionIsThrown(): void {
		$donation = ValidMembershipApplication::newDomainEntity();

		$repository = new DoctrineApplicationRepository( ThrowingEntityManager::newInstance( $this ) );

		$this->expectException( StoreMembershipApplicationException::class );
		$repository->storeApplication( $donation );
	}

	public function testWhenMembershipApplicationInDatabase_itIsReturnedAsMatchingDomainEntity(): void {
		$this->storeDoctrineApplication( ValidMembershipApplication::newDoctrineEntity() );

		$expected = ValidMembershipApplication::newAutoConfirmedDomainEntity();
		$expected->assignId( self::MEMBERSHIP_APPLICATION_ID );

		$this->assertEquals(
			$expected,
			$this->newRepository()->getApplicationById( self::MEMBERSHIP_APPLICATION_ID )
		);
	}

	private function storeDoctrineApplication( DoctrineApplication $application ): void {
		$this->entityManager->persist( $application );
		$this->entityManager->flush();
	}

	public function testWhenEntityDoesNotExist_getEntityReturnsNull(): void {
		$this->assertNull( $this->newRepository()->getApplicationById( self::ID_OF_APPLICATION_NOT_IN_DB ) );
	}

	public function testWhenReadFails_domainExceptionIsThrown(): void {
		$repository = new DoctrineApplicationRepository( ThrowingEntityManager::newInstance( $this ) );

		$this->expectException( GetMembershipApplicationException::class );
		$repository->getApplicationById( self::ID_OF_APPLICATION_NOT_IN_DB );
	}

	public function testWhenApplicationAlreadyExists_persistingCausesUpdate(): void {
		$repository = $this->newRepository();
		$originalApplication = ValidMembershipApplication::newDomainEntity();

		$repository->storeApplication( $originalApplication );

		// It is important a new instance is created here to test "detached entity" handling
		$newApplication = ValidMembershipApplication::newDomainEntity();
		$newApplication->assignId( $originalApplication->getId() );
		$newApplication->getApplicant()->changeEmailAddress( new EmailAddress( 'chuck.norris@always.win' ) );

		$repository->storeApplication( $newApplication );

		$doctrineApplication = $this->getApplicationFromDatabase( $newApplication->getId() );

		$this->assertSame( 'chuck.norris@always.win', $doctrineApplication->getApplicantEmailAddress() );
	}

	public function testWriteAndReadRoundtrip(): void {
		$repository = $this->newRepository();
		$application = ValidMembershipApplication::newAutoConfirmedDomainEntity();

		$repository->storeApplication( $application );

		$this->assertEquals(
			$application,
			$repository->getApplicationById( self::MEMBERSHIP_APPLICATION_ID )
		);
	}

	public function testWhenPersistingDeletedApplication_exceptionIsThrown(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->assignId( self::ID_OF_APPLICATION_NOT_IN_DB );

		$repository = $this->newRepository();

		$this->expectException( StoreMembershipApplicationException::class );
		$repository->storeApplication( $application );
	}

	public function testWhenPersistingApplicationWithModerationFlag_doctrineApplicationHasFlag(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->markForModeration();

		$this->newRepository()->storeApplication( $application );
		$doctrineApplication = $this->getApplicationFromDatabase( $application->getId() );

		$this->assertTrue( $doctrineApplication->needsModeration() );
		$this->assertFalse( $doctrineApplication->isCancelled() );
	}

	public function testWhenPersistingApplicationWithCancelledFlag_doctrineApplicationHasFlag(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();

		$this->newRepository()->storeApplication( $application );
		$doctrineApplication = $this->getApplicationFromDatabase( $application->getId() );

		$this->assertFalse( $doctrineApplication->needsModeration() );
		$this->assertTrue( $doctrineApplication->isCancelled() );
	}

	public function testWhenPersistingCancelledModerationApplication_doctrineApplicationHasFlags(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->markForModeration();
		$application->cancel();

		$this->newRepository()->storeApplication( $application );
		$doctrineApplication = $this->getApplicationFromDatabase( $application->getId() );

		$this->assertTrue( $doctrineApplication->needsModeration() );
		$this->assertTrue( $doctrineApplication->isCancelled() );
	}

	public function testGivenDoctrineApplicationWithModerationAndCancelled_domainEntityHasFlags(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setStatus( DoctrineApplication::STATUS_CANCELED + DoctrineApplication::STATUS_MODERATION );

		$this->storeDoctrineApplication( $doctrineApplication );
		$application = $this->newRepository()->getApplicationById( $doctrineApplication->getId() );

		$this->assertTrue( $application->needsModeration() );
		$this->assertTrue( $application->isCancelled() );
	}

	public function testGivenDoctrineApplicationWithModerationFlag_domainEntityHasFlag(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setStatus( DoctrineApplication::STATUS_MODERATION );

		$this->storeDoctrineApplication( $doctrineApplication );
		$application = $this->newRepository()->getApplicationById( $doctrineApplication->getId() );

		$this->assertTrue( $application->needsModeration() );
		$this->assertFalse( $application->isCancelled() );
	}

	public function testGivenDoctrineApplicationWithCancelledFlag_domainEntityHasFlag(): void {
		$doctrineApplication = ValidMembershipApplication::newDoctrineEntity();
		$doctrineApplication->setStatus( DoctrineApplication::STATUS_CANCELED );

		$this->storeDoctrineApplication( $doctrineApplication );
		$application = $this->newRepository()->getApplicationById( $doctrineApplication->getId() );

		$this->assertFalse( $application->needsModeration() );
		$this->assertTrue( $application->isCancelled() );
	}

	public function testGivenDoctrineApplicationWithCancelledFlag_initialStatusIsPreserved(): void {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();

		$this->newRepository()->storeApplication( $application );
		$doctrineApplication = $this->getApplicationFromDatabase( $application->getId() );

		$this->assertSame( DoctrineApplication::STATUS_CONFIRMED, $doctrineApplication->getDataObject()->getPreservedStatus() );
	}

	public function testGivenCompanyApplication_companyNameIsPersisted(): void {
		$this->newRepository()->storeApplication( ValidMembershipApplication::newCompanyApplication() );

		$expected = ValidMembershipApplication::newDoctrineCompanyEntity();
		$expected->setId( self::MEMBERSHIP_APPLICATION_ID );

		$actual = $this->getApplicationFromDatabase( self::MEMBERSHIP_APPLICATION_ID );

		$this->assertNotNull( $actual->getCompany() );
		$this->assertEquals( $expected->getCompany(), $actual->getCompany() );
	}

	public function testReadingAnonymizedApplication_anonymizedExceptionIsThrown(): void {
		$this->storeDoctrineApplication( ValidMembershipApplication::newAnonymizedDoctrineEntity() );

		$this->expectException( ApplicationAnonymizedException::class );

		$this->newRepository()->getApplicationById( self::MEMBERSHIP_APPLICATION_ID );
	}

}
