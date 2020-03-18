<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\UseCases\HandleSubscriptionSignupNotification;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineApplicationRepository;
use WMDE\Fundraising\MembershipContext\Infrastructure\TemplateMailerInterface;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FailingAuthorizer;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FakeApplicationRepository;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\SucceedingAuthorizer;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\TemplateBasedMailerSpy;
use WMDE\Fundraising\MembershipContext\UseCases\HandleSubscriptionSignupNotification\HandleSubscriptionSignupNotificationUseCase;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidSubscriptionSignupRequest;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ThrowingEntityManager;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\HandleSubscriptionSignupNotification\HandleSubscriptionSignupNotificationUseCase
 *
 * @licence GNU GPL v2+
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class HandleSubscriptionSignupNotificationUseCaseTest extends TestCase {

	/**
	 * @var TemplateBasedMailerSpy
	 */
	private $mailerSpy;

	public function setUp(): void {
		$this->mailerSpy = new TemplateBasedMailerSpy( $this );
	}

	public function testWhenPaymentMethodIsNotPayPal_requestIsNotHandled(): void {
		$fakeRepository = new FakeApplicationRepository();
		$fakeRepository->storeApplication( ValidMembershipApplication::newDomainEntity() );

		$useCase = new HandleSubscriptionSignupNotificationUseCase(
			$fakeRepository,
			new SucceedingAuthorizer(),
			$this->getMailer(),
			new NullLogger()
		);

		$request = ValidSubscriptionSignupRequest::newValidRequest();
		$response = $useCase->handleNotification( $request );
		$this->assertFalse( $response->notificationWasHandled() );
	}

	public function testWhenMembershipApplicationDoesNotExist_requestIsNotHandled(): void {
		$fakeRepository = new FakeApplicationRepository();
		$fakeRepository->storeApplication( ValidMembershipApplication::newDomainEntity() );

		$useCase = new HandleSubscriptionSignupNotificationUseCase(
			$fakeRepository,
			new SucceedingAuthorizer(),
			$this->getMailer(),
			new NullLogger()
		);

		$request = ValidSubscriptionSignupRequest::newValidRequest();
		$request->setApplicationId( 667 );
		$response = $useCase->handleNotification( $request );
		$this->assertFalse( $response->notificationWasHandled() );
	}

	public function testWhenRepositoryThrowsException_responseContainsErrors(): void {
		$useCase = new HandleSubscriptionSignupNotificationUseCase(
			new DoctrineApplicationRepository( ThrowingEntityManager::newInstance( $this ) ),
			new SucceedingAuthorizer(),
			$this->getMailer(),
			new NullLogger()
		);
		$request = ValidSubscriptionSignupRequest::newValidRequest();
		$response = $useCase->handleNotification( $request );
		$this->assertFalse( $response->notificationWasHandled() );
		$this->assertTrue( $response->hasErrors() );
	}

	public function testWhenAuthorizationFails_requestIsNotHandled(): void {
		$fakeRepository = new FakeApplicationRepository();
		$fakeRepository->storeApplication( ValidMembershipApplication::newDomainEntityUsingPayPal() );

		$useCase = new HandleSubscriptionSignupNotificationUseCase(
			$fakeRepository,
			new FailingAuthorizer(),
			$this->getMailer(),
			new NullLogger()
		);

		$request = ValidSubscriptionSignupRequest::newValidRequest();
		$response = $useCase->handleNotification( $request );
		$this->assertFalse( $response->notificationWasHandled() );
	}

	public function testWhenStatusIsNotAllowedForConfirming_requestIsNotHandled(): void {
		$fakeRepository = new FakeApplicationRepository();

		$application = ValidMembershipApplication::newDomainEntityUsingPayPal();
		$application->confirm();
		$fakeRepository->storeApplication( $application );

		$useCase = new HandleSubscriptionSignupNotificationUseCase(
			$fakeRepository,
			new SucceedingAuthorizer(),
			$this->getMailer(),
			new NullLogger()
		);

		$request = ValidSubscriptionSignupRequest::newValidRequest();
		$response = $useCase->handleNotification( $request );
		$this->assertFalse( $response->notificationWasHandled() );
		$this->assertTrue( $response->hasErrors() );
	}

	public function testWhenValidRequestIsSent_itIsHandled(): void {
		$fakeRepository = new FakeApplicationRepository();

		$application = ValidMembershipApplication::newDomainEntityUsingPayPal();
		$fakeRepository->storeApplication( $application );

		$useCase = new HandleSubscriptionSignupNotificationUseCase(
			$fakeRepository,
			new SucceedingAuthorizer(),
			$this->getMailer(),
			new NullLogger()
		);

		$request = ValidSubscriptionSignupRequest::newValidRequest();
		$response = $useCase->handleNotification( $request );
		$this->assertTrue( $response->notificationWasHandled() );
		$this->assertFalse( $response->hasErrors() );
	}

	public function testWhenApplicationIsConfirmed_mailIsSent(): void {
		$fakeRepository = new FakeApplicationRepository();

		$application = ValidMembershipApplication::newDomainEntityUsingPayPal();
		$fakeRepository->storeApplication( $application );

		$useCase = new HandleSubscriptionSignupNotificationUseCase(
			$fakeRepository,
			new SucceedingAuthorizer(),
			$this->mailerSpy,
			new NullLogger()
		);

		$request = ValidSubscriptionSignupRequest::newValidRequest();
		$useCase->handleNotification( $request );
		$this->mailerSpy->assertCalledOnce();
	}

	/**
	 * @return TemplateMailerInterface&MockObject
	 */
	private function getMailer(): TemplateMailerInterface {
		return $this->createMock( TemplateMailerInterface::class );
	}

}
