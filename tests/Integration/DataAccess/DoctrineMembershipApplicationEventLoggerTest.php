<?php

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineMembershipApplicationEventLogger;
use WMDE\Fundraising\MembershipContext\Infrastructure\MembershipApplicationEventLogException;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\ThrowingEntityManagerTrait;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;

#[CoversClass( DoctrineMembershipApplicationEventLogger::class )]
#[CoversClass( MembershipApplicationEventLogException::class )]
class DoctrineMembershipApplicationEventLoggerTest extends TestCase {

	use ThrowingEntityManagerTrait;

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
		$logger = new DoctrineMembershipApplicationEventLogger( $this->getThrowingEntityManager() );

		$this->expectException( MembershipApplicationEventLogException::class );
		$logger->log( 1234, self::DEFAULT_MESSAGE );
	}

	public function testWhenPersistFails_domainExceptionIsThrown(): void {
		$entityManager = $this->createConfiguredStub(
			EntityManager::class,
			[ 'find' => new MembershipApplication() ]
		);

		$entityManager->method( 'persist' )
			->willThrowException( new class( 'This is a test exception' ) extends RuntimeException implements ORMException {
			} );

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

		$logger = new DoctrineMembershipApplicationEventLogger( $this->entityManager );
		$logger->log( self::MEMBERSHIP_APPLICATION_ID, self::DEFAULT_MESSAGE );

		$data = $this->getMembershipApplicationDataById( self::MEMBERSHIP_APPLICATION_ID );

		$this->assertArrayHasKey( 'log', $data );
		$this->assertIsIterable( $data['log'] );
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

		$logger = new DoctrineMembershipApplicationEventLogger( $this->entityManager );
		$logger->log( self::MEMBERSHIP_APPLICATION_ID, self::DEFAULT_MESSAGE );

		$data = $this->getMembershipApplicationDataById( self::MEMBERSHIP_APPLICATION_ID );

		$this->assertArrayHasKey( 'log', $data );
		$this->assertIsIterable( $data['log'] );
		$this->assertCount( 2, $data['log'] );
		$this->assertContains( 'We call her the log lady', $data['log'] );
		$this->assertContains( self::DEFAULT_MESSAGE, $data['log'] );
	}

	/**
	 * @param int $membershipApplicationId
	 *
	 * @return array<string, mixed>
	 */
	private function getMembershipApplicationDataById( int $membershipApplicationId ): array {
		$application = $this->entityManager->find( MembershipApplication::class, $membershipApplicationId );
		if ( $application === null ) {
			throw new LogicException( 'Membership application not found' );
		}
		return $application->getDecodedData();
	}
}
