<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\UseCases\CancelMembershipApplication;

use WMDE\Fundraising\MembershipContext\Authorization\ApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\Domain\Model\Application;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FailingAuthorizer;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\InMemoryApplicationRepository;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\SucceedingAuthorizer;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\TemplateBasedMailerSpy;
use WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication\CancellationRequest;
use WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication\CancelMembershipApplicationUseCase;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\CancelMembershipApplication\CancelMembershipApplicationUseCase
 *
 * @license GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class CancelMembershipApplicationUseCaseTest extends \PHPUnit\Framework\TestCase {

	private const ID_OF_NON_EXISTING_APPLICATION = 1337;

	/**
	 * @var ApplicationAuthorizer
	 */
	private $authorizer;

	/**
	 * @var ApplicationRepository
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
		$this->repository = new InMemoryApplicationRepository();
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

	public function testWhenApplicationGetsCancelled_cancellationConfirmationEmailIsSend(): void {
		$application = $this->cancelableApplication;
		$this->newUseCase()->cancelApplication( new CancellationRequest( $application->getid() ) );

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

	public function testWhenAuthorizationFails_cancellationFails(): void {
		$this->authorizer = new FailingAuthorizer();

		$response = $this->newUseCase()->cancelApplication(
			new CancellationRequest( $this->cancelableApplication->getid() )
		);

		$this->assertFalse( $response->cancellationWasSuccessful() );
	}

}
