<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Unit\UseCases\ModerateMembershipApplication;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Data\ValidMembershipApplication;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FakeApplicationRepository;
use WMDE\Fundraising\MembershipContext\UseCases\ModerateMembershipApplication\ModerateMembershipApplicationUseCase;

/**
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ModerateMembershipApplication\ModerateMembershipApplicationUseCase
 * @covers \WMDE\Fundraising\MembershipContext\UseCases\ModerateMembershipApplication\ModerateMembershipApplicationResponse
 */
class ModerateMembershipApplicationUseCaseTest extends TestCase {

	private const AUTH_USER_NAME = "Pintman Paddy Losty";

	public function testModerateNonExistentMembershipApplication_actionFails() {
		$useCase = $this->newUseCase();

		$response = $useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );

		$this->assertFalse( $response->moderationChangeSucceeded() );
	}

	public function testApproveNonExistentMembershipApplication_actionFails() {
		$useCase = $this->newUseCase();

		$response = $useCase->approveMembershipApplication( 1, self::AUTH_USER_NAME );

		$this->assertFalse( $response->moderationChangeSucceeded() );
	}

	public function testSetModerateOnMembershipApplication_setsModerated() {
		$application = ValidMembershipApplication::newDomainEntity();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );

		$this->assertTrue( $response->moderationChangeSucceeded() );
	}

	public function testSetModerateOnMembershipApplication_returnsMembershipApplicationId() {
		$application = ValidMembershipApplication::newDomainEntity();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );

		$this->assertSame( 1, $response->getMembershipApplication() );
	}

	public function testSetModerateOnMembershipApplicationThatIsMarkedForModeration_actionFails() {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->markForModeration();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );

		$this->assertFalse( $response->moderationChangeSucceeded() );
	}

	public function testSetModerateOnCancelledMembershipApplication_actionFails() {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->cancel();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->markMembershipApplicationAsModerated( 1, self::AUTH_USER_NAME );

		$this->assertFalse( $response->moderationChangeSucceeded() );
	}

	public function testApproveMembershipApplication_removesModerated() {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->markForModeration();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->approveMembershipApplication( 1, self::AUTH_USER_NAME );

		$this->assertTrue( $response->moderationChangeSucceeded() );
	}

	public function testApproveOnApprovedMembershipApplication_actionFails() {
		$application = ValidMembershipApplication::newDomainEntity();
		$application->approve();
		$useCase = $this->newUseCase( $application );

		$response = $useCase->approveMembershipApplication( 1, self::AUTH_USER_NAME );

		$this->assertFalse( $response->moderationChangeSucceeded() );
	}

	private function newUseCase( MembershipApplication ...$applications ): ModerateMembershipApplicationUseCase {
		return new ModerateMembershipApplicationUseCase( new FakeApplicationRepository( ...$applications ) );
	}
}
