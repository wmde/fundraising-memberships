<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\DataAccess;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Authorization\ApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities\MembershipApplication;
use WMDE\Fundraising\MembershipContext\DataAccess\MembershipApplicationData;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;

/**
 * @covers \WMDE\Fundraising\MembershipContext\DataAccess\DoctrineApplicationAuthorizer
 */
class DoctrineMembershipApplicationAuthorizerTest extends TestCase {

	private const CORRECT_UPDATE_TOKEN = 'CorrectUpdateToken';
	private const CORRECT_ACCESS_TOKEN = 'CorrectAccessToken';
	private const WRONG__UPDATE_TOKEN = 'WrongUpdateToken';
	private const WRONG_ACCESS_TOKEN = 'WrongAccessToken';
	private const EMPTY_TOKEN = '';
	private const MEANINGLESS_APPLICATION_ID = 1337;
	private const DUMMY_PAYMENT_ID = 42;

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

	public function testGivenNoMembershipApplication_authorizationFails(): void {
		$authorizer = $this->newAuthorizer( self::CORRECT_UPDATE_TOKEN, self::CORRECT_ACCESS_TOKEN );
		$this->assertFalse( $authorizer->canModifyApplication( self::MEANINGLESS_APPLICATION_ID ) );
		$this->assertFalse( $authorizer->canAccessApplication( self::MEANINGLESS_APPLICATION_ID ) );
	}

	/**
	 * @dataProvider updateTokenProvider
	 */
	public function testGivenMembershipApplication_authorizerChecksUpdateToken( string $updateToken, bool $expectedResult ): void {
		$application = $this->givenMembershipApplication();
		$authorizer = $this->newAuthorizer( $updateToken );
		$this->assertSame( $expectedResult, $authorizer->canModifyApplication( $application->getId() ) );
	}

	/**
	 * @return iterable<string,array{string,bool}>
	 */
	public static function updateTokenProvider(): iterable {
		yield 'correct update token' => [ self::CORRECT_UPDATE_TOKEN, true ];
		yield 'incorrect update token' => [ self::WRONG__UPDATE_TOKEN, false ];
	}

	/**
	 * @dataProvider accessTokenProvider
	 */
	public function testGivenMembershipApplication_authorizerChecksAccessToken( string $accessToken, bool $expectedResult ): void {
		$application = $this->givenMembershipApplication();
		$authorizer = $this->newAuthorizer( '', $accessToken );
		$this->assertSame( $expectedResult, $authorizer->canAccessApplication( $application->getId() ) );
	}

	/**
	 * @return iterable<string,array{string,bool}>
	 */
	public static function accessTokenProvider(): iterable {
		yield 'correct access token' => [ self::CORRECT_ACCESS_TOKEN, true ];
		yield 'incorrect update token' => [ self::WRONG_ACCESS_TOKEN, false ];
	}

	public function testGivenMembershipWithoutToken_updateAuthorizationFails(): void {
		$application = new MembershipApplication();
		$application->setPaymentId( self::DUMMY_PAYMENT_ID );
		$this->storeApplication( $application );
		$authorizer = $this->newAuthorizer( 'SomeToken', self::EMPTY_TOKEN );

		$this->assertFalse( $authorizer->canModifyApplication( $application->getId() ) );
	}

	public function testGivenMembershipWithoutTokenAndEmptyAccessToken_accessAuthorizationFails(): void {
		$application = new MembershipApplication();
		$application->setPaymentId( self::DUMMY_PAYMENT_ID );
		$this->storeApplication( $application );
		$authorizer = $this->newAuthorizer( 'SomeToken', self::EMPTY_TOKEN );

		$this->assertFalse( $authorizer->canAccessApplication( $application->getId() ) );
	}

	public function testGivenMembershipWithoutTokenAndEmptyUpdateToken_updateAuthorizationFails(): void {
		$application = new MembershipApplication();
		$application->setPaymentId( self::DUMMY_PAYMENT_ID );
		$this->storeApplication( $application );
		$authorizer = $this->newAuthorizer( self::EMPTY_TOKEN, 'SomeToken' );

		$this->assertFalse( $authorizer->canModifyApplication( $application->getId() ) );
	}

	public function testGivenMembershipWithoutToken_accessAuthorizationFails(): void {
		$application = new MembershipApplication();
		$application->setPaymentId( self::DUMMY_PAYMENT_ID );
		$this->storeApplication( $application );
		$authorizer = $this->newAuthorizer( self::EMPTY_TOKEN, 'SomeToken' );

		$this->assertFalse( $authorizer->canAccessApplication( $application->getId() ) );
	}

	public function testWhenDoctrineThrowsException_modificationCheckFails(): void {
		$authorizer = $this->newAuthorizerWithFailingDoctrine();
		$this->assertFalse( $authorizer->canModifyApplication( self::MEANINGLESS_APPLICATION_ID ) );
	}

	public function testWhenDoctrineThrowsException_accessCheckFails(): void {
		$authorizer = $this->newAuthorizerWithFailingDoctrine();
		$this->assertFalse( $authorizer->canAccessApplication( self::MEANINGLESS_APPLICATION_ID ) );
	}

	private function getThrowingEntityManager(): EntityManager {
		$entityManager = $this->getMockBuilder( EntityManager::class )
			->disableOriginalConstructor()->getMock();

		$entityManager->method( $this->anything() )
			->willThrowException( new ORMException() );

		return $entityManager;
	}

	private function givenMembershipApplication(): MembershipApplication {
		$application = new MembershipApplication();
		$application->setPaymentId( self::DUMMY_PAYMENT_ID );
		$application->modifyDataObject( static function ( MembershipApplicationData $data ): void {
			$data->setUpdateToken( self::CORRECT_UPDATE_TOKEN );
			$data->setAccessToken( self::CORRECT_ACCESS_TOKEN );
		} );
		$this->storeApplication( $application );
		return $application;
	}

	private function newAuthorizerWithFailingDoctrine() {
		return new DoctrineApplicationAuthorizer(
			$this->getThrowingEntityManager(),
			self::CORRECT_UPDATE_TOKEN,
			self::CORRECT_ACCESS_TOKEN
		);
	}

}
