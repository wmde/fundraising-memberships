<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\UseCases\UpdateMembershipApplication;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\MembershipContext\Authorization\MembershipAuthorizationChecker;
use WMDE\Fundraising\MembershipContext\Domain\Event\MembershipUpdatedEvent;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantAddress;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantName;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\MembershipRepository;
use WMDE\Fundraising\MembershipContext\EventEmitter;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\EventEmitterSpy;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\FailingMembershipAuthorizationChecker;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\FakeMembershipRepository;
use WMDE\Fundraising\MembershipContext\Tests\TestDoubles\SucceedingMembershipAuthorizationChecker;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\Notification\MembershipNotifier;
use WMDE\Fundraising\MembershipContext\UseCases\UpdateMembershipApplication\UpdateMembershipApplicationRequest;
use WMDE\Fundraising\MembershipContext\UseCases\UpdateMembershipApplication\UpdateMembershipApplicationResponse;
use WMDE\Fundraising\MembershipContext\UseCases\UpdateMembershipApplication\UpdateMembershipApplicationUseCase;
use WMDE\Fundraising\MembershipContext\UseCases\UpdateMembershipApplication\UpdateMembershipApplicationValidationResult;
use WMDE\Fundraising\MembershipContext\UseCases\UpdateMembershipApplication\UpdateMembershipApplicationValidator;
use WMDE\FunValidators\ConstraintViolation;

#[CoversClass( UpdateMembershipApplicationUseCase::class )]
#[CoversClass( UpdateMembershipApplicationRequest::class )]
#[CoversClass( UpdateMembershipApplicationResponse::class )]
class UpdateMembershipApplicationUseCaseTest extends TestCase {

	public function testGivenValidPersonApplication_applicationIsUpdated(): void {
		$repository = $this->newMembershipRepository();
		$useCase = $this->newUpdateMembershipApplicationUseCase( repository: $repository );

		$application = ValidMembershipApplication::newApplication();
		$repository->storeApplication( $application );

		$updatedSalutation = 'Mrs.';
		$updatedTitle = 'Dr.';
		$updatedFirstName = 'Updated First Name';
		$updatedLastName = 'Updated Last Name';
		$updatedStreet = 'Updated person straße 2';
		$updatedPostalCode = '27272';
		$updatedCity = 'Updated person city';
		$updatedCountryCode = 'DE';
		$updatedEmail = 'updated@email.com';

		$response = $useCase->updateMembershipApplication(
			new UpdateMembershipApplicationRequest(
				$application->getId(),
				false,
				$updatedSalutation,
				$updatedTitle,
				$updatedFirstName,
				$updatedLastName,
				'',
				$updatedStreet,
				$updatedPostalCode,
				$updatedCity,
				$updatedCountryCode,
				new EmailAddress( $updatedEmail )
			)
		);

		$application = $repository->getMembershipApplicationById( $application->getId() );

		$this->assertNotNull( $application );
		$this->assertTrue( $response->isSuccessful() );

		$applicant = $application->getApplicant();

		$this->assertEquals(
			ApplicantName::newPrivatePersonName(
				$updatedSalutation,
				$updatedTitle,
				$updatedFirstName,
				$updatedLastName
			),
			$applicant->getName()
		);

		$this->assertEquals(
			new ApplicantAddress(
				$updatedStreet,
				$updatedPostalCode,
				$updatedCity,
				$updatedCountryCode
			),
			$applicant->getPhysicalAddress()
		);

		$this->assertEquals(
			new EmailAddress( $updatedEmail ),
			$applicant->getEmailAddress()
		);
	}

	public function testGivenValidCompanyApplication_applicationIsUpdated(): void {
		$repository = $this->newMembershipRepository();
		$useCase = $this->newUpdateMembershipApplicationUseCase( repository: $repository );

		$application = ValidMembershipApplication::newCompanyApplication();
		$repository->storeApplication( $application );

		$updatedCompanyName = 'Updated GmbH';
		$updatedStreet = 'Updated company straße 3';
		$updatedPostalCode = '36363';
		$updatedCity = 'Updated company city';
		$updatedCountryCode = 'DE';
		$updatedEmail = 'updated.company@email.com';

		$response = $useCase->updateMembershipApplication(
			new UpdateMembershipApplicationRequest(
				$application->getId(),
				true,
				'',
				'',
				'',
				'',
				$updatedCompanyName,
				$updatedStreet,
				$updatedPostalCode,
				$updatedCity,
				$updatedCountryCode,
				new EmailAddress( $updatedEmail )
			)
		);

		$application = $repository->getMembershipApplicationById( $application->getId() );

		$this->assertNotNull( $application );
		$this->assertTrue( $response->isSuccessful() );

		$applicant = $application->getApplicant();

		$this->assertEquals(
			ApplicantName::newCompanyName(
				$updatedCompanyName
			),
			$applicant->getName()
		);

		$this->assertEquals(
			new ApplicantAddress(
				$updatedStreet,
				$updatedPostalCode,
				$updatedCity,
				$updatedCountryCode
			),
			$applicant->getPhysicalAddress()
		);

		$this->assertEquals(
			new EmailAddress( $updatedEmail ),
			$applicant->getEmailAddress()
		);
	}

	public function testIfMembershipCannotBeModified_updateFails(): void {
		$repository = $this->newMembershipRepository();

		$application = ValidMembershipApplication::newApplication();
		$repository->storeApplication( $application );

		$useCase = $this->newUpdateMembershipApplicationUseCase(
			repository: $repository,
			authorizationChecker: new FailingMembershipAuthorizationChecker()
		);

		$response = $useCase->updateMembershipApplication(
			$this->newUpdateMembershipApplicationRequestForPerson(
				$application->getId()
			)
		);

		$this->assertFalse( $response->isSuccessful() );
		$this->assertEquals(
			UpdateMembershipApplicationResponse::ERROR_ACCESS_DENIED,
			$response->getErrorMessage()
		);
	}

	public function testMembershipNotFound_updateFails(): void {
		$repository = $this->newMembershipRepository();

		$useCase = $this->newUpdateMembershipApplicationUseCase(
			repository: $repository
		);

		$response = $useCase->updateMembershipApplication(
			$this->newUpdateMembershipApplicationRequestForPerson( 999 )
		);

		$this->assertFalse( $response->isSuccessful() );
		$this->assertEquals(
			UpdateMembershipApplicationResponse::ERROR_MEMBERSHIP_APPLICATION_NOT_FOUND,
			$response->getErrorMessage()
		);
	}

	public function testGivenExportedMembership_updateFails(): void {
		$repository = $this->newMembershipRepository();

		$application = ValidMembershipApplication::newApplication();
		$application->setExported();
		$repository->storeApplication( $application );

		$useCase = $this->newUpdateMembershipApplicationUseCase( repository: $repository );

		$response = $useCase->updateMembershipApplication(
			$this->newUpdateMembershipApplicationRequestForPerson(
				$application->getId()
			)
		);

		$this->assertFalse( $response->isSuccessful() );
		$this->assertEquals(
			UpdateMembershipApplicationResponse::ERROR_MEMBERSHIP_APPLICATION_IS_EXPORTED,
			$response->getErrorMessage()
		);
	}

	public function testOnUpdate_emitsMembershipUpdatedEvent(): void {
		$repository = $this->newMembershipRepository();
		$eventEmitter = new EventEmitterSpy();

		$application = ValidMembershipApplication::newApplication();
		$repository->storeApplication( $application );

		$previousApplicant = $application->getApplicant();

		$useCase = $this->newUpdateMembershipApplicationUseCase(
			repository: $repository,
			eventEmitter: $eventEmitter
		);

		$updatedSalutation = 'Mrs.';
		$updatedTitle = 'Dr.';
		$updatedFirstName = 'Updated First Name';
		$updatedLastName = 'Updated First Name';
		$updatedStreet = 'Updated straße 4';
		$updatedPostalCode = '14141';
		$updatedCity = 'Updated city';
		$updatedCountryCode = 'DE';
		$updatedEmail = 'updated@email.com';

		$useCase->updateMembershipApplication(
			new UpdateMembershipApplicationRequest(
				$application->getId(),
				false,
				$updatedSalutation,
				$updatedTitle,
				$updatedFirstName,
				$updatedLastName,
				'',
				$updatedStreet,
				$updatedPostalCode,
				$updatedCity,
				$updatedCountryCode,
				new EmailAddress( $updatedEmail )
			)
		);

		/** @var MembershipUpdatedEvent[] $events */
		$events = $eventEmitter->getEvents();

		$this->assertCount( 1, $events );

		/** @phpstan-ignore-next-line method.alreadyNarrowedType */
		$this->assertInstanceOf( MembershipUpdatedEvent::class, $events[0] );

		$this->assertSame( $application->getId(), $events[0]->getMembershipId() );

		$this->assertEquals( $previousApplicant, $events[0]->getPreviousApplicant() );

		$expectedUpdatedApplicant = $events[0]->getNewApplicant();

		$this->assertEquals(
			ApplicantName::newPrivatePersonName(
				$updatedSalutation,
				$updatedTitle,
				$updatedFirstName,
				$updatedLastName
			),
			$expectedUpdatedApplicant->getName()
		);

		$this->assertEquals(
			new ApplicantAddress(
				$updatedStreet,
				$updatedPostalCode,
				$updatedCity,
				$updatedCountryCode
			),
			$expectedUpdatedApplicant->getPhysicalAddress()
		);

		$this->assertEquals(
			new EmailAddress( $updatedEmail ),
			$expectedUpdatedApplicant->getEmailAddress()
		);

		$this->assertNotSame(
			$events[0]->getPreviousApplicant(),
			$events[0]->getNewApplicant(),
			'Event should contain a new applicant instance'
		);
	}

	public function testGivenValidApplication_confirmationMailIsSent(): void {
		$repository = $this->newMembershipRepository();

		$application = ValidMembershipApplication::newApplication();
		$repository->storeApplication( $application );

		$mailer = $this->createMock( MembershipNotifier::class );

		$mailer->expects( $this->once() )
			->method( 'sendConfirmationFor' )
			->with( $application );

		$useCase = $this->newUpdateMembershipApplicationUseCase(
			repository: $repository,
			membershipConfirmationMailer: $mailer
		);

		$response = $useCase->updateMembershipApplication(
			$this->newUpdateMembershipApplicationRequestForPerson(
				$application->getId()
			)
		);

		$this->assertTrue( $response->isSuccessful() );
	}

	public function testGivenCancelledMembership_updateFails(): void {
		$repository = $this->newMembershipRepository();

		$application = ValidMembershipApplication::newApplication();
		$application->cancel();
		$repository->storeApplication( $application );

		$useCase = $this->newUpdateMembershipApplicationUseCase( repository: $repository );

		$response = $useCase->updateMembershipApplication(
			$this->newUpdateMembershipApplicationRequestForPerson(
				$application->getId()
			)
		);

		$this->assertFalse( $response->isSuccessful() );
		$this->assertEquals(
			UpdateMembershipApplicationResponse::ERROR_MEMBERSHIP_APPLICATION_NOT_FOUND,
			$response->getErrorMessage()
		);
	}

	public function testGivenFailingValidation_updateFails(): void {
		$repository = $this->newMembershipRepository();

		$validator = $this->createConfiguredStub(
			UpdateMembershipApplicationValidator::class,
			[
				'validateMembershipApplicationData' =>
					new UpdateMembershipApplicationValidationResult(
						new ConstraintViolation(
							'',
							'invalid_first_name',
							'first_name'
						)
					)
			]
		);

		$application = ValidMembershipApplication::newApplication();
		$repository->storeApplication( $application );

		$useCase = $this->newUpdateMembershipApplicationUseCase(
			repository: $repository,
			validator: $validator
		);

		$response = $useCase->updateMembershipApplication(
			$this->newUpdateMembershipApplicationRequestForPerson(
				$application->getId()
			)
		);

		$this->assertFalse( $response->isSuccessful() );
		$this->assertEquals(
			UpdateMembershipApplicationResponse::ERROR_VALIDATION_FAILED,
			$response->getErrorMessage()
		);
	}

	private function newMembershipRepository(): MembershipRepository {
		return new FakeMembershipRepository();
	}

	private function newMembershipAuthorizationChecker(): MembershipAuthorizationChecker {
		return new SucceedingMembershipAuthorizationChecker();
	}

	private function newUpdateMembershipApplicationValidator(): UpdateMembershipApplicationValidator {
		return $this->createConfiguredStub(
			UpdateMembershipApplicationValidator::class,
			[
				'validateMembershipApplicationData' => new UpdateMembershipApplicationValidationResult()
			]
		);
	}

	private function newMembershipConfirmationMailer(): MembershipNotifier {
		return $this->createStub( MembershipNotifier::class );
	}

	private function newUpdateMembershipApplicationUseCase(
		MembershipRepository $repository,
		?MembershipAuthorizationChecker $authorizationChecker = null,
		?UpdateMembershipApplicationValidator $validator = null,
		?MembershipNotifier $membershipConfirmationMailer = null,
		?EventEmitter $eventEmitter = null
	): UpdateMembershipApplicationUseCase {
		return new UpdateMembershipApplicationUseCase(
			$authorizationChecker ?? $this->newMembershipAuthorizationChecker(),
				$validator ?? $this->newUpdateMembershipApplicationValidator(),
			$repository,
				$membershipConfirmationMailer ?? $this->newMembershipConfirmationMailer(),
				$eventEmitter ?? $this->createStub( EventEmitter::class )
		);
	}

	private function newUpdateMembershipApplicationRequestForPerson(
		int $applicationId
	): UpdateMembershipApplicationRequest {
		return new UpdateMembershipApplicationRequest(
			$applicationId,
			false,
			ValidMembershipApplication::APPLICANT_SALUTATION,
			ValidMembershipApplication::APPLICANT_TITLE,
			ValidMembershipApplication::APPLICANT_FIRST_NAME,
			ValidMembershipApplication::APPLICANT_LAST_NAME,
			'',
			ValidMembershipApplication::APPLICANT_STREET_ADDRESS,
			ValidMembershipApplication::APPLICANT_POSTAL_CODE,
			ValidMembershipApplication::APPLICANT_CITY,
			ValidMembershipApplication::APPLICANT_COUNTRY_CODE,
			new EmailAddress(
				ValidMembershipApplication::APPLICANT_EMAIL_ADDRESS
			)
		);
	}
}
