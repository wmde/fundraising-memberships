<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Codeception\Specify;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Authorization\ApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\MembershipApplicationData;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;

/**
 * @covers \WMDE\Fundraising\MembershipContext\DataAccess\DoctrineApplicationAuthorizer
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DoctrineMembershipApplicationAuthorizerTest extends TestCase {
	use Specify;

	private const CORRECT_UPDATE_TOKEN = 'CorrectUpdateToken';
	private const CORRECT_ACCESS_TOKEN = 'CorrectAccessToken';
	private const WRONG__UPDATE_TOKEN = 'WrongUpdateToken';
	private const WRONG_ACCESS_TOKEN = 'WrongAccessToken';
	private const EMPTY_TOKEN = '';
	private const MEANINGLESS_APPLICATION_ID = 1337;
	private const ID_OF_WRONG_APPLICATION = 42;

	private EntityManager $entityManager;

	protected function setUp(): void {
		$this->entityManager = TestEnvironment::newInstance()->getEntityManager();
	}

	private function newAuthorizer( string $updateToken = '', string $accessToken = '' ): ApplicationAuthorizer {
		return new DoctrineApplicationAuthorizer( $this->entityManager, $updateToken, $accessToken );
	}

	private function storeApplication( MembershipApplication $application ): void {
		$this->entityManager->persist( $application );
		$this->entityManager->flush();
	}

	/**
	 * @slowThreshold 400
	 */
	public function testWhenNoMembershipApplications(): void {
		$this->specify( 'update authorization fails', function (): void {
			$authorizer = $this->newAuthorizer( self::CORRECT_UPDATE_TOKEN );
			$this->assertFalse( $authorizer->canModifyApplication( self::MEANINGLESS_APPLICATION_ID ) );
		} );

		$this->specify( 'access authorization fails', function (): void {
			$authorizer = $this->newAuthorizer( self::CORRECT_ACCESS_TOKEN );
			$this->assertFalse( $authorizer->canAccessApplication( self::MEANINGLESS_APPLICATION_ID ) );
		} );
	}

	/**
	 * @slowThreshold 1200
	 */
	public function testWhenApplicationWithTokenExists(): void {
		$application = new MembershipApplication();
		$application->modifyDataObject( static function ( MembershipApplicationData $data ): void {
			$data->setUpdateToken( self::CORRECT_UPDATE_TOKEN );
			$data->setAccessToken( self::CORRECT_ACCESS_TOKEN );
		} );
		$this->storeApplication( $application );

		$this->specify(
			'given correct application id and correct token, update authorization succeeds',
			function () use ( $application ): void {
				$authorizer = $this->newAuthorizer( self::CORRECT_UPDATE_TOKEN );
				$this->assertTrue( $authorizer->canModifyApplication( $application->getId() ) );
			}
		);

		$this->specify(
			'given wrong application id and correct token, update authorization fails',
			function (): void {
				$authorizer = $this->newAuthorizer( self::CORRECT_UPDATE_TOKEN );
				$this->assertFalse( $authorizer->canModifyApplication( self::ID_OF_WRONG_APPLICATION ) );
			}
		);

		$this->specify(
			'given correct application id and wrong token, update authorization fails',
			function () use ( $application ): void {
				$authorizer = $this->newAuthorizer( self::WRONG__UPDATE_TOKEN );
				$this->assertFalse( $authorizer->canModifyApplication( $application->getId() ) );
			}
		);

		$this->specify(
			'given correct application id and correct token, access authorization succeeds',
			function () use ( $application ): void {
				$authorizer = $this->newAuthorizer( self::EMPTY_TOKEN, self::CORRECT_ACCESS_TOKEN );
				$this->assertTrue( $authorizer->canAccessApplication( $application->getId() ) );
			}
		);

		$this->specify(
			'given wrong application id and correct token, access authorization fails',
			function (): void {
				$authorizer = $this->newAuthorizer( self::EMPTY_TOKEN, self::CORRECT_ACCESS_TOKEN );
				$this->assertFalse( $authorizer->canAccessApplication( self::ID_OF_WRONG_APPLICATION ) );
			}
		);

		$this->specify(
			'given correct application id and wrong token, access authorization fails',
			function () use ( $application ): void {
				$authorizer = $this->newAuthorizer( self::EMPTY_TOKEN, self::WRONG_ACCESS_TOKEN );
				$this->assertFalse( $authorizer->canAccessApplication( $application->getId() ) );
			}
		);
	}

	public function testGivenMembershipWithoutToken_updateAuthorizationFails(): void {
		$application = new MembershipApplication();
		$this->storeApplication( $application );
		$authorizer = $this->newAuthorizer( 'SomeToken', self::EMPTY_TOKEN );

		$this->assertFalse( $authorizer->canModifyApplication( $application->getId() ) );
	}

	public function testGivenMembershipWithoutTokenAndEmptyAccessToken_accessAuthorizationFails(): void {
		$application = new MembershipApplication();
		$this->storeApplication( $application );
		$authorizer = $this->newAuthorizer( 'SomeToken', self::EMPTY_TOKEN );

		$this->assertFalse( $authorizer->canAccessApplication( $application->getId() ) );
	}

	public function testGivenMembershipWithoutTokenAndEmptyUpdateToken_updateAuthorizationFails(): void {
		$application = new MembershipApplication();
		$this->storeApplication( $application );
		$authorizer = $this->newAuthorizer( self::EMPTY_TOKEN, 'SomeToken' );

		$this->assertFalse( $authorizer->canModifyApplication( $application->getId() ) );
	}

	public function testGivenMembershipWithoutToken_accessAuthorizationFails(): void {
		$application = new MembershipApplication();
		$this->storeApplication( $application );
		$authorizer = $this->newAuthorizer( self::EMPTY_TOKEN, 'SomeToken' );

		$this->assertFalse( $authorizer->canAccessApplication( $application->getId() ) );
	}

	/**
	 * @slowThreshold 400
	 */
	public function testWhenDoctrineThrowsException(): void {
		$authorizer = new DoctrineApplicationAuthorizer(
			$this->getThrowingEntityManager(),
			self::CORRECT_UPDATE_TOKEN,
			self::CORRECT_ACCESS_TOKEN
		);

		$this->specify( 'update authorization fails', function () use ( $authorizer ): void {
			$this->assertFalse( $authorizer->canModifyApplication( self::MEANINGLESS_APPLICATION_ID ) );
		} );

		$this->specify( 'access authorization fails', function () use ( $authorizer ): void {
			$this->assertFalse( $authorizer->canAccessApplication( self::MEANINGLESS_APPLICATION_ID ) );
		} );
	}

	private function getThrowingEntityManager(): EntityManager {
		$entityManager = $this->getMockBuilder( EntityManager::class )
			->disableOriginalConstructor()->getMock();

		$entityManager->method( $this->anything() )
			->willThrowException( new ORMException() );

		return $entityManager;
	}

}
