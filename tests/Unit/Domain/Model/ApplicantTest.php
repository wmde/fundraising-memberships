<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\Domain\Model;

use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\MembershipContext\Domain\Model\Applicant;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantAddress;
use WMDE\Fundraising\MembershipContext\Domain\Model\ApplicantName;
use WMDE\Fundraising\MembershipContext\Domain\Model\PhoneNumber;

/**
 * @covers \WMDE\Fundraising\MembershipContext\Domain\Model\Applicant
 */
class ApplicantTest extends \PHPUnit\Framework\TestCase {

	public function testWhenApplicantIsPrivatePerson_personTypeIsReturned(): void {
		$applicant = new Applicant(
			ApplicantName::newPrivatePersonName(),
			new ApplicantAddress(),
			new EmailAddress( 'test@wikimedia.de' ),
			new PhoneNumber( '01234567890' ),
			new \DateTime()
		);
		$this->assertTrue( $applicant->isPrivatePerson() );
		$this->assertFalse( $applicant->isCompany() );
	}

	public function testWhenApplicantIsCompany_companyTypeIsReturned(): void {
		$applicant = new Applicant(
			ApplicantName::newCompanyName(),
			new ApplicantAddress(),
			new EmailAddress( 'test@wikimedia.de' ),
			new PhoneNumber( '01234567890' ),
			new \DateTime()
		);
		$this->assertTrue( $applicant->isCompany() );
		$this->assertFalse( $applicant->isPrivatePerson() );
	}
}
