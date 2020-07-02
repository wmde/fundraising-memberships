<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\UseCases\CancelMembershipApplication;

use WMDE\Fundraising\MembershipContext\Authorization\ApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\Domain\Model\Application;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FailingAuthorizer;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FakeApplicationRepository;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\SucceedingAuthorizer;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\TemplateBasedMailerSpy;
use WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication\CancellationRequest;
use WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication\CancelMembershipApplicationUseCase;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication\CancelMembershipApplicationUseCase
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class CancelMembershipApplicationUseCaseTest extends \PHPUnit\Framework\TestCase {

	private const ID_OF_NON_EXISTING_APPLICATION = 1337;

	/**
	 * @var ApplicationAuthorizer
	 */
	private $authorizer;

	/**
	 * @var FakeApplicationRepository
	 */
	private $repository;

	/**
	 * @var TemplateBasedMailerSpy
	 */
	private $mailer;

	/**
	 * @var Application
	 */
	private $cancelableApplication;

	public function setUp(): void {
		$this->authorizer = new SucceedingAuthorizer();
		$this->repository = new FakeApplicationRepository();
		$this->mailer = new TemplateBasedMailerSpy( $this );

		$application = ValidMembershipApplication::newDomainEntity();
		$this->repository->storeApplication( $application );
		$this->cancelableApplication = $application;
	}

	public function testGivenIdOfUnknownDonation_cancellationIsNotSuccessful(): void {
		$response = $this->newUseCase()->cancelApplication(
			new CancellationRequest( self::ID_OF_NON_EXISTING_APPLICATION )
		);

		$this->assertFalse( $response->cancellationWasSuccessful() );
	}

	private function newUseCase(): CancelMembershipApplicationUseCase {
		return new CancelMembershipApplicationUseCase(
			$this->authorizer,
			$this->repository,
			$this->mailer
		);
	}

	public function testFailureResponseContainsApplicationId(): void {
		$response = $this->newUseCase()->cancelApplication(
			new CancellationRequest( self::ID_OF_NON_EXISTING_APPLICATION )
		);

		$this->assertSame(
			self::ID_OF_NON_EXISTING_APPLICATION,
			$response->getMembershipApplicationId()
		);
	}

	public function testGivenIdOfCancellableApplication_cancellationIsSuccessful(): void {
		$response = $this->newUseCase()->cancelApplication(
			new CancellationRequest( $this->cancelableApplication->getId() )
		);

		$this->assertTrue( $response->cancellationWasSuccessful() );
		$this->assertSame(
			$this->cancelableApplication->getId(),
			$response->getMembershipApplicationId()
		);
	}

	public function testWhenAuthorizationFails_cancellationFails(): void {
		$this->authorizer = new FailingAuthorizer();

		$response = $this->newUseCase()->cancelApplication(
			new CancellationRequest( $this->cancelableApplication->getId() )
		);

		$this->assertFalse( $response->cancellationWasSuccessful() );
	}

	public function testWhenSaveFails_cancellationFails() {
		$this->repository->throwOnWrite();

		$response = $this->newUseCase()->cancelApplication(
			new CancellationRequest( $this->cancelableApplication->getId() )
		);

		$this->assertFalse( $response->cancellationWasSuccessful() );
	}

	public function testWhenApplicationGetsCancelled_confirmationEmailIsSent(): void {
		$application = $this->cancelableApplication;
		$this->newUseCase()->cancelApplication( new CancellationRequest( $application->getId() ) );

		$this->mailer->assertCalledOnceWith(
			$application->getApplicant()->getEmailAddress(),
			[
				'membershipApplicant' => [
					'salutation' => $application->getApplicant()->getName()->getSalutation(),
					'title' => $application->getApplicant()->getName()->getTitle(),
					'lastName' => $application->getApplicant()->getName()->getLastName()
				],
			'applicationId' => 1
			]
		);
	}

	public function testWhenCancellationFails_confirmationEmailIsNotSend(): void {
		$this->newUseCase()->cancelApplication(
			new CancellationRequest( self::ID_OF_NON_EXISTING_APPLICATION )
		);

		$this->assertEmpty( $this->mailer->getSendMailCalls() );
	}

	public function testWhenApplicationIsAlreadyCancelled_onlySuccessResponseIsReturned(): void {
		$this->cancelableApplication->cancel();
		$this->repository->storeApplication( $this->cancelableApplication );
		$this->repository->throwOnWrite();
		$application = $this->cancelableApplication;

		$response = $this->newUseCase()->cancelApplication( new CancellationRequest( $application->getId() ) );

		$this->assertEmpty( $this->mailer->getSendMailCalls() );
		$this->assertTrue( $response->cancellationWasSuccessful() );
	}

}
