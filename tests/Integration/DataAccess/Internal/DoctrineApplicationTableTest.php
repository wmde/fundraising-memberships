<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess\Internal;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\Entities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\Internal\DoctrineApplicationTable;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ThrowingEntityManager;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;
use WMDE\PsrLogTestDoubles\LoggerSpy;

/**
 * @covers \WMDE\Fundraising\MembershipContext\DataAccess\Internal\DoctrineApplicationTable
 *
 * @license GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DoctrineApplicationTableTest extends TestCase {

	private const UNKNOWN_ID = 32205;
	private const IRRELEVANT_ID = 11111;

	/**
	 * @var EntityManager
	 */
	private $entityManager;

	/**
	 * @var LoggerSpy
	 */
	private $logger;

	public function setUp() {
		$this->entityManager = TestEnvironment::newInstance()->getFactory()->getEntityManager();
		$this->logger = new LoggerSpy();
	}

	public function testGivenUnknownId_getApplicationOrNullByIdReturnsNull() {
		$this->assertNull( $this->getTable()->getApplicationOrNullById( self::UNKNOWN_ID ) );
	}

	private function getTable(): DoctrineApplicationTable {
		return new DoctrineApplicationTable(
			$this->entityManager,
			$this->logger
		);
	}

	public function testGivenKnownId_getApplicationOrNullByIdReturnsTheApplication() {
		$application = ValidMembershipApplication::newDoctrineEntity();
		$table = $this->getTable();
		$table->persistApplication( $application );

		$this->assertEquals(
			$application,
			$table->getApplicationOrNullById( $application->getId() )
		);
	}

	public function testWhenDoctrineThrowsException_getApplicationOrNullByIdRethrowsIt() {
		$this->entityManager = ThrowingEntityManager::newInstance( $this );

		$table = $this->getTable();

		$this->expectException( GetMembershipApplicationException::class );
		$table->getApplicationOrNullById( self::IRRELEVANT_ID );
	}

	public function testGivenUnknownId_getApplicationByIdThrowsException() {
		$table = $this->getTable();

		$this->expectException( GetMembershipApplicationException::class );
		$table->getApplicationById( self::UNKNOWN_ID );
	}

	public function testGivenKnownId_getApplicationByIdReturnsTheApplication() {
		$application = ValidMembershipApplication::newDoctrineEntity();
		$table = $this->getTable();
		$table->persistApplication( $application );

		$this->assertEquals(
			$application,
			$table->getApplicationById( $application->getId() )
		);
	}

	public function testWhenDoctrineThrowsException_getApplicationByIdRethrowsIt() {
		$this->entityManager = ThrowingEntityManager::newInstance( $this );

		$table = $this->getTable();

		$this->expectException( GetMembershipApplicationException::class );
		$table->getApplicationById( self::IRRELEVANT_ID );
	}

	public function testWhenDoctrineThrowsException_persistApplicationRethrowsIt() {
		$this->entityManager = ThrowingEntityManager::newInstance( $this );

		$table = $this->getTable();

		$this->expectException( StoreMembershipApplicationException::class );
		$table->persistApplication( ValidMembershipApplication::newDoctrineEntity() );
	}

	public function testGivenUnknownId_modifyApplicationThrowsReadException() {
		$this->expectException( GetMembershipApplicationException::class );
		$this->getTable()->modifyApplication(
			self::UNKNOWN_ID,
			function( MembershipApplication $application ) {}
		);
	}

	public function testWhenDoctrineThrowsException_modifyApplicationRethrowsIt() {
		$application = ValidMembershipApplication::newDoctrineEntity();
		$table = $this->getTable();
		$table->persistApplication( $application );

		$this->makeEntityManagerThrowOnPersist();

		$this->expectException( StoreMembershipApplicationException::class );
		$table->modifyApplication(
			$application->getId(),
			function( MembershipApplication $application ) {}
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

	public function testGivenKnownId_modifyApplicationModifiesTheApplication() {
		$application = ValidMembershipApplication::newDoctrineEntity();
		$table = $this->getTable();
		$table->persistApplication( $application );

		$table->modifyApplication(
			$application->getId(),
			function( MembershipApplication $app ) {
				$app->setComment( 'Such a comment' );
			}
		);

		$this->assertSame(
			'Such a comment',
			$table->getApplicationById( $application->getId() )->getComment()
		);
	}

	public function testWhenDoctrineThrowsException_getApplicationOrNullLogsIt() {
		$this->entityManager = ThrowingEntityManager::newInstance( $this );

		try {
			$this->getTable()->getApplicationOrNullById( self::IRRELEVANT_ID );
		}
		catch ( \Exception $ex ) {}

		$this->assertNotEmpty( 1, $this->logger->getLogCalls() );
	}

	public function testWhenDoctrineThrowsException_persistApplicationLogsIt() {
		$this->entityManager = ThrowingEntityManager::newInstance( $this );

		try {
			$this->getTable()->persistApplication( ValidMembershipApplication::newDoctrineEntity() );
		}
		catch ( \Exception $ex ) {}

		$this->assertNotEmpty( 1, $this->logger->getLogCalls() );
	}

	public function testWhenDoctrineThrowsException_modifyApplicationLogsIt() {
		$this->entityManager = ThrowingEntityManager::newInstance( $this );

		try {
			$this->getTable()->modifyApplication( self::IRRELEVANT_ID, function() {} );
		}
		catch ( \Exception $ex ) {}

		$this->assertNotEmpty( 1, $this->logger->getLogCalls() );
	}

}