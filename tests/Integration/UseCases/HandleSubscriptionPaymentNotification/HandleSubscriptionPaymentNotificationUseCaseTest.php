<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\UseCases\HandleSubscriptionPaymentNotification;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use WMDE\Fundraising\MembershipContext\Infrastructure\TemplateMailerInterface;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidPayPalNotificationRequest;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineApplicationRepository;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FailingAuthorizer;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FakeApplicationRepository;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\SucceedingAuthorizer;
use WMDE\Fundraising\MembershipContext\UseCases\HandleSubscriptionPaymentNotification\HandleSubscriptionPaymentNotificationUseCase;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ThrowingEntityManager;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\HandleSubscriptionPaymentNotification\HandleSubscriptionPaymentNotificationUseCase
 *
 * @licence GNU GPL v2+
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class HandleSubscriptionPaymentNotificationUseCaseTest extends TestCase {

	public function testWhenRepositoryThrowsException_requestIsNotHandled(): void {
		$useCase = new HandleSubscriptionPaymentNotificationUseCase(
			new DoctrineApplicationRepository( ThrowingEntityManager::newInstance( $this ) ),
			new SucceedingAuthorizer(),
			$this->getMailer(),
			new NullLogger()
		);
		$request = ValidPayPalNotificationRequest::newInstantPayment( 1 );
		$response = $useCase->handleNotification( $request );
		$this->assertFalse( $response->notificationWasHandled() );
		$this->assertTrue( $response->hasErrors() );
	}

	public function testWhenApplicationDoesNotExist_requestIsNotHandled(): void {
		$fakeRepository = new FakeApplicationRepository();
		$fakeRepository->storeApplication( ValidMembershipApplication::newDomainEntityUsingPayPal() );

		$useCase = new HandleSubscriptionPaymentNotificationUseCase(
			$fakeRepository,
			new FailingAuthorizer(),
			$this->getMailer(),
			new NullLogger()
		);

		$request = ValidPayPalNotificationRequest::newInstantPayment( 667 );
		$response = $useCase->handleNotification( $request );
		$this->assertFalse( $response->notificationWasHandled() );
	}

	public function testWhenAuthorizationFails_requestIsNotHandled(): void {
		$fakeRepository = new FakeApplicationRepository();
		$fakeRepository->storeApplication( ValidMembershipApplication::newDomainEntityUsingPayPal() );

		$useCase = new HandleSubscriptionPaymentNotificationUseCase(
			$fakeRepository,
			new FailingAuthorizer(),
			$this->getMailer(),
			new NullLogger()
		);

		$request = ValidPayPalNotificationRequest::newInstantPayment( 1 );
		$response = $useCase->handleNotification( $request );
		$this->assertFalse( $response->notificationWasHandled() );
	}

	public function testWhenTransactionTypeIsForSubscriptionChanges_requestIsNotHandled(): void {
		$request = ValidPayPalNotificationRequest::newSubscriptionModification();

		$useCase = new HandleSubscriptionPaymentNotificationUseCase(
			new FakeApplicationRepository(),
			new SucceedingAuthorizer(),
			$this->getMailer(),
			new NullLogger()
		);
		$response = $useCase->handleNotification( $request );
		$this->assertFalse( $response->notificationWasHandled() );
	}

	public function testGivenSubscriptionPaymentRequest_childDataSetIsCreated(): void {
		$application = ValidMembershipApplication::newConfirmedSubscriptionDomainEntity();

		$fakeRepository = new FakeApplicationRepository();
		$fakeRepository->storeApplication( $application );

		$request = ValidPayPalNotificationRequest::newRecurringPayment( $application->getId() );

		$useCase = new HandleSubscriptionPaymentNotificationUseCase(
			$fakeRepository,
			new SucceedingAuthorizer(),
			$this->getMailer(),
			new NullLogger()
		);

		$response = $useCase->handleNotification( $request );
		$this->assertTrue( $response->notificationWasHandled() );
		$this->assertFalse( $response->hasErrors() );

		$application = $fakeRepository->getApplicationById( $application->getId() );
		/** @var \WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment $payment */
		$payment = $application->getPayment()->getPaymentMethod();
		$childApplication = $fakeRepository->getApplicationById( $payment->getPayPalData()->getChildPaymentEntityId( ValidPayPalNotificationRequest::TRANSACTION_ID ) );
		$this->assertNotNull( $childApplication );
		/** @var \WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment $childPayment */
		$childPayment = $childApplication->getPayment()->getPaymentMethod();
		$this->assertEquals( ValidPayPalNotificationRequest::TRANSACTION_ID, $childPayment->getPayPalData()->getPaymentId() );
		$this->assertEquals( $application->getPayment()->getAmount(), $childApplication->getPayment()->getAmount() );
		$this->assertEquals( $application->getApplicant(), $childApplication->getApplicant() );
		$this->assertEquals( $application->getPayment()->getIntervalInMonths(), $childApplication->getPayment()->getIntervalInMonths() );
		$this->assertTrue( $childApplication->isConfirmed() );
		$this->assertTrue( $childApplication->getDonationReceipt() );
	}

	public function testGivenExistingTransactionId_requestIsNotHandled(): void {
		$application = ValidMembershipApplication::newConfirmedSubscriptionDomainEntity();
		/** @var PayPalPayment $payment */
		$payment = $application->getPayment()->getPaymentMethod();
		$payment->getPayPalData()->addChildPayment( ValidPayPalNotificationRequest::TRANSACTION_ID, 1 );

		$fakeRepository = new FakeApplicationRepository();
		$fakeRepository->storeApplication( $application );

		$request = ValidPayPalNotificationRequest::newRecurringPayment( 1 );

		$useCase = new HandleSubscriptionPaymentNotificationUseCase(
			$fakeRepository,
			new SucceedingAuthorizer(),
			$this->getMailer(),
			new NullLogger()
		);

		$response = $useCase->handleNotification( $request );
		$this->assertFalse( $response->notificationWasHandled() );
	}

	/**
	 * @return TemplateMailerInterface&MockObject
	 */
	private function getMailer(): TemplateMailerInterface {
		return $this->createMock( TemplateMailerInterface::class );
	}

	public function testGivenSubscriptionPaymentRequest_parentDataSetReferencesChildPaymentId(): void {
		$application = ValidMembershipApplication::newConfirmedSubscriptionDomainEntity();

		$fakeRepository = new FakeApplicationRepository();
		$fakeRepository->storeApplication( $application );

		$request = ValidPayPalNotificationRequest::newRecurringPayment( $application->getId() );

		$useCase = new HandleSubscriptionPaymentNotificationUseCase(
			$fakeRepository,
			new SucceedingAuthorizer(),
			$this->getMailer(),
			new NullLogger()
		);
		$useCase->handleNotification( $request );

		/** @var PayPalPayment $payment */
		$payment = $application->getPayment()->getPaymentMethod();

		$storedApplication = $fakeRepository->getApplicationById( $application->getId() );
		/** @var PayPalPayment $storedpayment */
		$storedpayment = $storedApplication->getPayment()->getPaymentMethod();

		$this->assertSame(
			2,
			$storedpayment->getPayPalData()->getChildPaymentEntityId( ValidPayPalNotificationRequest::TRANSACTION_ID )
		);

		$this->assertEquals(
			$payment->getPayPalData()->addChildPayment( ValidPayPalNotificationRequest::TRANSACTION_ID, 2 ),
			$storedpayment->getPayPalData()
		);
	}
}
