<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\UpdateMembershipApplication;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidatorPatterns;
use WMDE\Fundraising\MembershipContext\UseCases\UpdateMembershipApplication\UpdateMembershipApplicationRequest;
use WMDE\Fundraising\MembershipContext\UseCases\UpdateMembershipApplication\UpdateMembershipApplicationValidator;
use WMDE\FunValidators\ConstraintViolation;
use WMDE\FunValidators\SucceedingDomainNameValidator;
use WMDE\FunValidators\ValidationResult;
use WMDE\FunValidators\Validators\AddressValidator;
use WMDE\FunValidators\Validators\EmailValidator;
use WMDE\FunValidators\Validators\SucceedingEmailValidator;

#[CoversClass( UpdateMembershipApplicationValidator::class )]
class UpdateMembershipApplicationValidatorTest extends TestCase {

	public function testGivenFailingAddressValidator_validationFails(): void {
		$addressViolation = new ValidationResult( new ConstraintViolation( '', 'membership_applicant_name_missing', 'first_name' ) );
		$applicantValidator = $this->createConfiguredStub(
			AddressValidator::class,
			[ 'validatePersonName' => $addressViolation ]
		);
		$validator = new UpdateMembershipApplicationValidator( $applicantValidator, new SucceedingEmailValidator() );
		$result = $validator->validateMembershipApplicationData( $this->newEmptyUpdateMembershipApplicationRequest() );

		$this->assertFalse( $result->isSuccessful() );
		$this->assertEquals(
			$addressViolation->getViolations()[0],
			$result->getFirstViolation()
		);
	}

	public function testGivenEmptyMembershipApplicationRequestValues_validationFails(): void {
		$validator = new UpdateMembershipApplicationValidator(
			new AddressValidator( ValidatorPatterns::COUNTRY_POSTCODE, ValidatorPatterns::ADDRESS_PATTERNS ),
			new EmailValidator( new SucceedingDomainNameValidator() )
		);
		$result = $validator->validateMembershipApplicationData( $this->newEmptyUpdateMembershipApplicationRequest() );
		$violations = $result->getViolations();

		$this->assertFalse( $result->isSuccessful() );
		$this->assertEquals( 'salutation', $violations[0]->getSource() );
		$this->assertEquals( 'firstName', $violations[1]->getSource() );
		$this->assertEquals( 'lastName', $violations[2]->getSource() );
		$this->assertEquals( 'street', $violations[3]->getSource() );
		$this->assertEquals( 'postcode', $violations[4]->getSource() );
		$this->assertEquals( 'city', $violations[5]->getSource() );
		$this->assertEquals( 'country', $violations[6]->getSource() );
	}

	public function testGivenInvalidCompanyMembershipApplication_validationFails(): void {
		$validator = new UpdateMembershipApplicationValidator(
			new AddressValidator( ValidatorPatterns::COUNTRY_POSTCODE, ValidatorPatterns::ADDRESS_PATTERNS ),
			new EmailValidator( new SucceedingDomainNameValidator() )
		);
		$result = $validator->validateMembershipApplicationData( $this->newInvalidCompanyUpdateMembershipApplicationRequest() );
		$violations = $result->getViolations();

		$this->assertFalse( $result->isSuccessful() );
		$this->assertEquals( 'companyName', $violations[0]->getSource() );
		$this->assertEquals( 'street', $violations[1]->getSource() );
		$this->assertEquals( 'postcode', $violations[2]->getSource() );
		$this->assertEquals( 'city', $violations[3]->getSource() );
		$this->assertEquals( 'country', $violations[4]->getSource() );
	}

	private function newEmptyUpdateMembershipApplicationRequest(): UpdateMembershipApplicationRequest {
		return new UpdateMembershipApplicationRequest(
			membershipId: 1,
			isCompanyApplication: false,
			salutation: '',
			title: '',
			firstName: '',
			lastName: '',
			companyName: '',
			streetAddress: '',
			postalCode: '',
			city: '',
			countryCode: '',
			emailAddress: new EmailAddress( 'empty.email.is.validated@email-address.repo.com' )
		);
	}

	private function newInvalidCompanyUpdateMembershipApplicationRequest(): UpdateMembershipApplicationRequest {
		return new UpdateMembershipApplicationRequest(
			membershipId: 1,
			isCompanyApplication: true,
			salutation: '',
			title: '',
			firstName: '',
			lastName: '',
			companyName: str_repeat( 'TEST', 26 ),
			streetAddress: str_repeat( 'TEST', 26 ),
			postalCode: str_repeat( '1', 17 ),
			city: str_repeat( 'TEST', 26 ),
			countryCode: str_repeat( 'TEST', 26 ),
			emailAddress: new EmailAddress( 'invalid.email.is.validated@email-address.repo.com' )
		);
	}
}
