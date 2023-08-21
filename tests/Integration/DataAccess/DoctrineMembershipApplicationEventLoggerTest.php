<?php

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipApplicationEventLogger;
use WMDE\Fundraising\MembershipContext\Infrastructure\MembershipApplicationEventLogException;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\ThrowingEntityManager;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;

/**
 * @covers \WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipApplicationEventLogger
 * @covers \WMDE\Fundraising\MembershipContext\Infrastructure\MembershipApplicationEventLogException
 */
class DoctrineMembershipApplicationEventLoggerTest extends TestCase {

	private const MEMBERSHIP_APPLICATION_ID = 12345;

	public const DEFAULT_MESSAGE = 'Itchy, Tasty';
	private const DUMMY_PAYMENT_ID = 42;

	private EntityManager $entityManager;

	public function setUp(): void {
		$this->entityManager = TestEnvironment::newInstance()->getEntityManager();
	}

	public function testGivenApplicationThatDoesNotExist_throwsException(): void {
		$logger = new DoctrineMembershipApplicationEventLogger( $this->entityManager );

		$this->expectException( MembershipApplicationEventLogException::class );
		$logger->log( 1234, self::DEFAULT_MESSAGE );
	}

	public function testWhenFetchFails_domainExceptionIsThrown(): void {
		$logger = new DoctrineMembershipApplicationEventLogger( ThrowingEntityManager::newInstance( $this ) );

		$this->expectException( MembershipApplicationEventLogException::class );
		$logger->log( 1234, self::DEFAULT_MESSAGE );
	}

	public function testWhenPersistFails_domainExceptionIsThrown(): void {
		$entityManager = $this->getMockBuilder( EntityManager::class )
			->disableOriginalConstructor()->getMock();
		$entityManager->expects( $this->any() )->method( 'find' )->willReturn( new MembershipApplication() );
		$entityManager->expects( $this->any() )->method( 'persist' )->willThrowException(
			new ORMException( 'This is a test exception' )
		);

		$logger = new DoctrineMembershipApplicationEventLogger( $entityManager );

		$this->expectException( MembershipApplicationEventLogException::class );
		$logger->log( 1234, self::DEFAULT_MESSAGE );
	}

	public function testGivenMessageAndNoLogExists_createsLog(): void {
		$application = new MembershipApplication();
		$application->setId( self::MEMBERSHIP_APPLICATION_ID );
		$application->setPaymentId( self::DUMMY_PAYMENT_ID );
		$this->entityManager->persist( $application );
		$this->entityManager->flush();
		$applicationId = $application->getId();

		$logger = new DoctrineMembershipApplicationEventLogger( $this->entityManager );
		$logger->log( $applicationId, self::DEFAULT_MESSAGE );

		$data = $this->getMembershipApplicationDataById( $applicationId );

		$this->assertArrayHasKey( 'log', $data );
		$this->assertCount( 1, $data['log'] );
		$this->assertContains( self::DEFAULT_MESSAGE, $data['log'] );
	}

	public function testGivenMessageAndLogExists_addsRow(): void {
		$application = new MembershipApplication();
		$application->setId( self::MEMBERSHIP_APPLICATION_ID );
		$application->setPaymentId( self::DUMMY_PAYMENT_ID );
		$application->encodeAndSetData( [ 'log' => [ '2021-01-01 0:00:00' => 'We call her the log lady' ] ] );
		$this->entityManager->persist( $application );
		$this->entityManager->flush();
		$applicationId = $application->getId();

		$logger = new DoctrineMembershipApplicationEventLogger( $this->entityManager );
		$logger->log( $applicationId, self::DEFAULT_MESSAGE );

		$data = $this->getMembershipApplicationDataById( $applicationId );

		$this->assertArrayHasKey( 'log', $data );
		$this->assertCount( 2, $data['log'] );
		$this->assertContains( 'We call her the log lady', $data['log'] );
		$this->assertContains( self::DEFAULT_MESSAGE, $data['log'] );
	}

	private function getMembershipApplicationDataById( int $membershipApplicationId ): array {
		$application = $this->entityManager->find( MembershipApplication::class, $membershipApplicationId );
		return $application->getDecodedData();
	}
}
