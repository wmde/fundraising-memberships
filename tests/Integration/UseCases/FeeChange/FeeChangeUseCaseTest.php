<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\Tests\Integration\UseCases\FeeChange;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\MembershipContext\DataAccess\DoctrineFeeChangeRepository;
use WMDE\Fundraising\MembershipContext\Domain\Model\FeeChange;
use WMDE\Fundraising\MembershipContext\Domain\Model\FeeChangeState;
use WMDE\Fundraising\MembershipContext\Infrastructure\PaymentServiceFactory;
use WMDE\Fundraising\MembershipContext\Tests\Fixtures\FeeChanges;
use WMDE\Fundraising\MembershipContext\Tests\TestEnvironment;
use WMDE\Fundraising\MembershipContext\UseCases\FeeChange\FeeChangeRequest;
use WMDE\Fundraising\MembershipContext\UseCases\FeeChange\FeeChangeResponse;
use WMDE\Fundraising\MembershipContext\UseCases\FeeChange\FeeChangeUseCase;
use WMDE\Fundraising\MembershipContext\UseCases\FeeChange\ShowFeeChangePresenter;
use WMDE\Fundraising\PaymentContext\Domain\PaymentType;
use WMDE\Fundraising\PaymentContext\Services\URLAuthenticator;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\CreatePaymentUseCase;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\FailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentCreationRequest;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\SuccessResponse;

#[CoversClass( FeeChangeUseCase::class )]
#[CoversClass( FeeChangeRequest::class )]
#[CoversClass( FeeChangeResponse::class )]
class FeeChangeUseCaseTest extends TestCase {

	private const string UUID_NEW_FEE_CHANGE = FeeChanges::UUID_1;
	private const string UUID_FILLED_FEE_CHANGE = FeeChanges::UUID_2;
	private const string UUID_EXPORTED_FEE_CHANGE = FeeChanges::UUID_3;
	private const string UUID_MISSING_FEE_CHANGE = FeeChanges::UUID_4;

	private EntityManager $entityManager;

	public function setUp(): void {
		$this->entityManager = TestEnvironment::newInstance()->getEntityManager();
		$this->insertFeeChanges();
	}

	public function newFeeChangeUseCase(
		?CreatePaymentUseCase $createPaymentUseCase = null,
		?URLAuthenticator $urlAuthenticator = null
	): FeeChangeUseCase {
		return new FeeChangeUseCase(
			new DoctrineFeeChangeRepository( $this->entityManager ),
				new PaymentServiceFactory( $createPaymentUseCase ?? $this->newSucceedingCreatePaymentUseCase(), [ PaymentType::DirectDebit, PaymentType::FeeChange ] ),
				$urlAuthenticator ?? $this->newUrlAuthenticator()
		);
	}

	public function testShowsFeeChange(): void {
		$presenter = $this->createMock( ShowFeeChangePresenter::class );
		$useCase = $this->newFeeChangeUseCase();

		$presenter->expects( $this->once() )->method( 'showFeeChangeForm' )->with(
			self::UUID_NEW_FEE_CHANGE,
			FeeChanges::EXTERNAL_MEMBER_ID,
			FeeChanges::AMOUNT,
			FeeChanges::SUGGESTED_AMOUNT,
			FeeChanges::INTERVAL
		);

		$useCase->showFeeChange( self::UUID_NEW_FEE_CHANGE, $presenter );
	}

	public function testShowsFeeChangeAlreadyFilled(): void {
		$presenter = $this->createMock( ShowFeeChangePresenter::class );
		$useCase = $this->newFeeChangeUseCase();

		$presenter->expects( $this->once() )->method( 'showFeeChangeAlreadyFilled' );

		$useCase->showFeeChange( self::UUID_FILLED_FEE_CHANGE, $presenter );
	}

	public function testShowsFeeChangeError(): void {
		$presenter = $this->createMock( ShowFeeChangePresenter::class );
		$useCase = $this->newFeeChangeUseCase();

		$presenter->expects( $this->once() )->method( 'showFeeChangeError' );

		$useCase->showFeeChange( self::UUID_MISSING_FEE_CHANGE, $presenter );
	}

	public function testChangeExistingFeeUpdatesFeeChange(): void {
		$createPaymentUseCase = $this->newSucceedingCreatePaymentUseCase();
		$useCase = $this->newFeeChangeUseCase( createPaymentUseCase:  $createPaymentUseCase );

		$createPaymentUseCase->expects( $this->once() )->method( 'createPayment' )->willReturnCallback( function ( PaymentCreationRequest $request ) {
			$this->assertEquals( FeeChanges::AMOUNT, $request->amountInEuroCents );
			$this->assertEquals( FeeChanges::INTERVAL, $request->interval );
			$this->assertEquals( PaymentType::FeeChange->value, $request->paymentType );

			return new SuccessResponse(
				FeeChanges::PAYMENT_ID,
				'',
				true
			);
		} );

		$this->assertEquals(
			new FeeChangeResponse( true ),
			$useCase->changeFee( new FeeChangeRequest( self::UUID_NEW_FEE_CHANGE, FeeChanges::MEMBER_NAME, FeeChanges::AMOUNT, FeeChanges::PAYMENT_TYPE ) )
		);

		/** @var FeeChange $storedFeeChange */
		$storedFeeChange = $this->entityManager->find( FeeChange::class, 1 );

		$this->assertEquals( FeeChangeState::FILLED, $storedFeeChange->getState() );
	}

	public function testChangeExistingFeeUpdatesFeeChangeWithIBAN(): void {
		$createPaymentUseCase = $this->newSucceedingCreatePaymentUseCase();
		$useCase = $this->newFeeChangeUseCase( createPaymentUseCase:  $createPaymentUseCase );

		$createPaymentUseCase->expects( $this->once() )->method( 'createPayment' )->willReturnCallback( function ( PaymentCreationRequest $request ) {
			$this->assertEquals( FeeChanges::AMOUNT, $request->amountInEuroCents );
			$this->assertEquals( FeeChanges::INTERVAL, $request->interval );
			$this->assertEquals( PaymentType::DirectDebit->value, $request->paymentType );
			$this->assertEquals( 'DE02120300000000202051', $request->iban );
			$this->assertEquals( 'BYLADEM1001', $request->bic );

			return new SuccessResponse(
				FeeChanges::PAYMENT_ID,
				'',
				true
			);
		} );

		$this->assertEquals(
			new FeeChangeResponse( true ),
			$useCase->changeFee( new FeeChangeRequest(
				self::UUID_NEW_FEE_CHANGE,
				FeeChanges::MEMBER_NAME,
				FeeChanges::AMOUNT,
				PaymentType::DirectDebit->value,
				'DE02120300000000202051',
				'BYLADEM1001'
			) )
		);

		/** @var FeeChange $storedFeeChange */
		$storedFeeChange = $this->entityManager->find( FeeChange::class, 1 );

		$this->assertEquals( FeeChangeState::FILLED, $storedFeeChange->getState() );
	}

	public function testChangeNonExistingFeeReturnsFailureResponse(): void {
		$useCase = $this->newFeeChangeUseCase();

		$this->assertEquals(
			new FeeChangeResponse( false, [ 'exception' => 'Could not find FeeChange with uuid ' . self::UUID_MISSING_FEE_CHANGE ] ),
			$useCase->changeFee( new FeeChangeRequest( self::UUID_MISSING_FEE_CHANGE, FeeChanges::MEMBER_NAME, FeeChanges::AMOUNT, FeeChanges::PAYMENT_TYPE ) )
		);
	}

	public function testHandlesEmptyMemberName(): void {
		$useCase = $this->newFeeChangeUseCase();

		$this->assertEquals(
			new FeeChangeResponse( false, [ 'member_name_required' => 'Member name is required' ] ),
			$useCase->changeFee( new FeeChangeRequest( self::UUID_NEW_FEE_CHANGE, '', FeeChanges::AMOUNT, FeeChanges::PAYMENT_TYPE ) )
		);
	}

	public function testHandlesPaymentCreationFailure(): void {
		$useCase = $this->newFeeChangeUseCase( createPaymentUseCase: $this->newFailingCreatePaymentUseCase( 'Payment did a fail' ) );

		$this->assertEquals(
			new FeeChangeResponse( false, [ 'payment' => 'Payment did a fail' ] ),
			$useCase->changeFee( new FeeChangeRequest( self::UUID_NEW_FEE_CHANGE, FeeChanges::MEMBER_NAME, FeeChanges::AMOUNT, FeeChanges::PAYMENT_TYPE ) )
		);
	}

	/**
	 * @return iterable<array<string>>
	 */
	public static function nonNewFeeChangeProvider(): iterable {
		yield [ self::UUID_FILLED_FEE_CHANGE ];
		yield [ self::UUID_EXPORTED_FEE_CHANGE ];
	}

	#[DataProvider( 'nonNewFeeChangeProvider' )]
	public function testChangeNonNewFeeChangeReturnsFailureResponse( string $feeChangeUUID ): void {
		$useCase = $this->newFeeChangeUseCase();

		$this->assertEquals(
			new FeeChangeResponse( false, [ 'fee_change_already_submitted' => "This fee change ({$feeChangeUUID}) was already submitted" ] ),
			$useCase->changeFee( new FeeChangeRequest( $feeChangeUUID, FeeChanges::MEMBER_NAME, FeeChanges::AMOUNT, FeeChanges::PAYMENT_TYPE ) )
		);
	}

	private function newSucceedingCreatePaymentUseCase(): CreatePaymentUseCase&MockObject {
		$useCaseMock = $this->createMock( CreatePaymentUseCase::class );

		$successResponse = new SuccessResponse(
			FeeChanges::PAYMENT_ID,
			'',
			true
		);

		$useCaseMock->method( 'createPayment' )->willReturn( $successResponse );
		return $useCaseMock;
	}

	private function newFailingCreatePaymentUseCase( string $message ): CreatePaymentUseCase&MockObject {
		$useCaseMock = $this->createMock( CreatePaymentUseCase::class );

		$successResponse = new FailureResponse( $message );

		$useCaseMock->method( 'createPayment' )->willReturn( $successResponse );
		return $useCaseMock;
	}

	private function newUrlAuthenticator(): URLAuthenticator {
		$authenticator = $this->createStub( URLAuthenticator::class );
		$authenticator->method( 'addAuthenticationTokensToApplicationUrl' )->willReturnArgument( 0 );
		$authenticator->method( 'getAuthenticationTokensForPaymentProviderUrl' )->willReturn( [] );
		return $authenticator;
	}

	private function insertFeeChanges(): void {
		$this->entityManager->persist( FeeChanges::newNewFeeChange( self::UUID_NEW_FEE_CHANGE ) );
		$this->entityManager->persist( FeeChanges::newFilledFeeChange( self::UUID_FILLED_FEE_CHANGE ) );
		$this->entityManager->persist( FeeChanges::newExportedFeeChange( self::UUID_EXPORTED_FEE_CHANGE ) );
		$this->entityManager->flush();
	}
}
