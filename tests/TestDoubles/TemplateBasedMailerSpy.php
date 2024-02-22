<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\TestDoubles;

use LogicException;
use PHPUnit\Framework\TestCase;
use WMDE\EmailAddress\EmailAddress;
use WMDE\Fundraising\MembershipContext\Infrastructure\TemplateMailerInterface;

class TemplateBasedMailerSpy implements TemplateMailerInterface {

	/**
	 * @var array{EmailAddress,array<string, mixed>}[]
	 */
	private array $sendMailCalls = [];

	public function __construct( private readonly TestCase $testCase ) {
	}

	public function sendMail( EmailAddress $recipient, array $templateArguments = [] ): void {
		$this->sendMailCalls[] = [ $recipient, $templateArguments ];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getTemplateArgumentsFromFirstCall(): array {
		if ( count( $this->sendMailCalls ) === 0 ) {
			throw new LogicException( "sendMail() was not called, no calls to retrieve" );
		}
		$firstCall = $this->sendMailCalls[0];
		return $firstCall[1];
	}

	/**
	 * @param EmailAddress $expectedEmail
	 * @param array<string, mixed> $expectedArguments
	 *
	 * @return void
	 */
	public function assertCalledOnceWith( EmailAddress $expectedEmail, array $expectedArguments ): void {
		$this->assertWasCalledOnce();

		$this->testCase->assertEquals(
			[
				$expectedEmail,
				$expectedArguments
			],
			$this->sendMailCalls[0]
		);
	}

	public function assertWasCalledOnce(): void {
		$this->testCase->assertCount( 1, $this->sendMailCalls, 'Mailer should be called exactly once' );
	}

	public function assertWasNeverCalled(): void {
		$this->testCase->assertCount( 0, $this->sendMailCalls, 'Mailer should not be called' );
	}

}
