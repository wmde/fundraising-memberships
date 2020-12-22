<?php

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ApplyForMembership;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\MailTemplateValueBuilder;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\MailTemplateValueBuilder
 */
class MailTemplateValueBuilderTest extends TestCase {

	public function testBuildValuesForMembership(): void {
		$builder = new MailTemplateValueBuilder();

		$values = $builder->buildValuesForTemplate( ValidMembershipApplication::newDomainEntity() );

		$this->assertEquals(
			[
				'membershipType' => 'sustaining',
				'membershipFee' => '10.00',
				'paymentIntervalInMonths' => ValidMembershipApplication::PAYMENT_PERIOD_IN_MONTHS,
				'paymentType' => ValidMembershipApplication::PAYMENT_TYPE_DIRECT_DEBIT,
				'salutation' => ValidMembershipApplication::APPLICANT_SALUTATION,
				'title' => ValidMembershipApplication::APPLICANT_TITLE,
				'lastName' => ValidMembershipApplication::APPLICANT_LAST_NAME,
				'firstName' => ValidMembershipApplication::APPLICANT_FIRST_NAME,
				'hasReceiptEnabled' => ValidMembershipApplication::OPTS_INTO_DONATION_RECEIPT,
				'incentives' => []
			],
			$values
		);
	}

	public function testBuildValuesForMembershipWithIncentives(): void {
		$builder = new MailTemplateValueBuilder();

		$values = $builder->buildValuesForTemplate( ValidMembershipApplication::newApplicationWithIncentives() );

		$this->assertEquals( [ ValidMembershipApplication::INCENTIVE_NAME ], $values['incentives'] );
	}

}
