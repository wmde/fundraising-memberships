<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Fixtures;

use PHPUnit\Framework\TestCase;
use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\MembershipContext\Infrastructure\TemplateMailerInterface;

class TemplateBasedMailerSpy implements TemplateMailerInterface {

	private TestCase $testCase;
	private array $sendMailCalls = [];

	public function __construct( TestCase $testCase ) {
		$this->testCase = $testCase;
	}

	public function sendMail( EmailAddress $recipient, array $templateArguments = [] ): void {
		$this->sendMailCalls[] = [ $recipient, $templateArguments ];
	}

	public function getSendMailCalls(): array {
		return $this->sendMailCalls;
	}

	public function expectToBeCalledOnceWith( EmailAddress $expectedEmail, array $expectedArguments ): void {
		$this->expectToBeCalledOnce();

		$this->testCase->assertEquals(
			[
				$expectedEmail,
				$expectedArguments
			],
			$this->sendMailCalls[0]
		);
	}

	public function expectToBeCalledOnce(): void {
		$this->testCase->assertCount( 1, $this->sendMailCalls, 'Mailer should be called exactly once' );
	}

	public function expectToBeNotCalled(): void {
		$this->testCase->assertCount( 0, $this->sendMailCalls, 'Mailer should not be called' );
	}

}
