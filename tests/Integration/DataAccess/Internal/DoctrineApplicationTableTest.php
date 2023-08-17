<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess\Internal;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\Internal\DoctrineApplicationTable;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\ThrowingEntityManager;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;
use WMDE\PsrLogTestDoubles\LoggerSpy;

/**
 * @covers \WMDE\Fundraising\MembershipContext\DataAccess\Internal\DoctrineApplicationTable
 */
class DoctrineApplicationTableTest extends TestCase {

	private const KNOWN_ID = 12345;
	private const UNKNOWN_ID = 32205;

	private const IRRELEVANT_ID = 11111;

	private EntityManager $entityManager;

	private LoggerSpy $logger;

	public function setUp(): void {
		$this->entityManager = TestEnvironment::newInstance()->getEntityManager();
		$this->logger = new LoggerSpy();
	}

	public function testGivenUnknownId_getApplicationOrNullByIdReturnsNull(): void {
		$this->assertNull( $this->getTable()->getApplicationOrNullById( self::UNKNOWN_ID ) );
	}

	private function getTable(): DoctrineApplicationTable {
		return new DoctrineApplicationTable(
			$this->entityManager,
			$this->logger
		);
	}

	public function testGivenKnownId_getApplicationOrNullByIdReturnsTheApplication(): void {
		$application = ValidMembershipApplication::newDoctrineEntity( self::KNOWN_ID );
		$table = $this->getTable();
		$table->persistApplication( $application );

		$this->assertEquals(
			$application,
			$table->getApplicationOrNullById( self::KNOWN_ID )
		);
	}

	public function testWhenDoctrineThrowsException_getApplicationOrNullByIdRethrowsIt(): void {
		$this->entityManager = ThrowingEntityManager::newInstance( $this );

		$table = $this->getTable();

		$this->expectException( GetMembershipApplicationException::class );
		$table->getApplicationOrNullById( self::IRRELEVANT_ID );
	}

	public function testGivenUnknownId_getApplicationByIdThrowsException(): void {
		$table = $this->getTable();

		$this->expectException( GetMembershipApplicationException::class );
		$table->getApplicationById( self::UNKNOWN_ID );
	}

	public function testGivenKnownId_getApplicationByIdReturnsTheApplication(): void {
		$application = ValidMembershipApplication::newDoctrineEntity( self::KNOWN_ID );
		$table = $this->getTable();
		$table->persistApplication( $application );

		$this->assertEquals(
			$application,
			$table->getApplicationById( self::KNOWN_ID )
		);
	}

	public function testWhenDoctrineThrowsException_getApplicationByIdRethrowsIt(): void {
		$this->entityManager = ThrowingEntityManager::newInstance( $this );

		$table = $this->getTable();

		$this->expectException( GetMembershipApplicationException::class );
		$table->getApplicationById( self::IRRELEVANT_ID );
	}

	public function testWhenDoctrineThrowsException_persistApplicationRethrowsIt(): void {
		$this->entityManager = ThrowingEntityManager::newInstance( $this );

		$table = $this->getTable();

		$this->expectException( StoreMembershipApplicationException::class );
		$table->persistApplication( ValidMembershipApplication::newDoctrineEntity() );
	}

	public function testGivenUnknownId_modifyApplicationThrowsReadException(): void {
		$this->expectException( GetMembershipApplicationException::class );
		$this->getTable()->modifyApplication(
			self::UNKNOWN_ID,
			static function ( MembershipApplication $application ) {
			}
		);
	}

	public function testWhenDoctrineThrowsException_modifyApplicationRethrowsIt(): void {
		$application = ValidMembershipApplication::newDoctrineEntity();
		$table = $this->getTable();
		$table->persistApplication( $application );

		$this->makeEntityManagerThrowOnPersist();

		$this->expectException( StoreMembershipApplicationException::class );
		$table->modifyApplication(
			$application->getId(),
			static function ( MembershipApplication $application ) {
			}
		);
	}

	private function makeEntityManagerThrowOnPersist() {
		$this->entityManager->getEventManager()->addEventListener(
			\Doctrine\ORM\Events::onFlush,
			new class() {
				public function onFlush() {
					throw new ORMException();
				}
			}
		);
	}

	public function testGivenKnownId_modifyApplicationModifiesTheApplication(): void {
		$application = ValidMembershipApplication::newDoctrineEntity( self::KNOWN_ID );
		$table = $this->getTable();
		$table->persistApplication( $application );

		$table->modifyApplication(
			$application->getId(),
			static function ( MembershipApplication $app ) {
				$app->setComment( 'Such a comment' );
			}
		);

		$this->assertSame(
			'Such a comment',
			$table->getApplicationById( self::KNOWN_ID )->getComment()
		);
	}

	public function testWhenDoctrineThrowsException_getApplicationOrNullLogsIt(): void {
		$this->entityManager = ThrowingEntityManager::newInstance( $this );

		try {
			$this->getTable()->getApplicationOrNullById( self::IRRELEVANT_ID );
		} catch ( \Exception $ex ) {
		}

		$this->assertNotEmpty( $this->logger->getLogCalls() );
	}

	public function testWhenDoctrineThrowsException_persistApplicationLogsIt(): void {
		$this->entityManager = ThrowingEntityManager::newInstance( $this );

		try {
			$this->getTable()->persistApplication( ValidMembershipApplication::newDoctrineEntity() );
		} catch ( \Exception $ex ) {
		}

		$this->assertNotEmpty( $this->logger->getLogCalls() );
	}

	public function testWhenDoctrineThrowsException_modifyApplicationLogsIt(): void {
		$this->entityManager = ThrowingEntityManager::newInstance( $this );

		try {
			$this->getTable()->modifyApplication( self::IRRELEVANT_ID, static function () {
			} );
		} catch ( \Exception $ex ) {
		}

		$this->assertNotEmpty( $this->logger->getLogCalls() );
	}

}
