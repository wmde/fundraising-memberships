<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\FeeChange;

use WMDE\Fundraising\MembershipContext\Domain\FeeChangeException;
use WMDE\Fundraising\MembershipContext\Domain\Model\FeeChange;
use WMDE\Fundraising\MembershipContext\Domain\Model\FeeChangeState;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\FeeChangeRepository;
use WMDE\Fundraising\MembershipContext\Infrastructure\PaymentServiceFactory;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicantType;
use WMDE\Fundraising\MembershipContext\UseCases\ApplyForMembership\ApplicationValidationResult;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Services\URLAuthenticator;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\FailureResponse;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentCreationRequest;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentParameters;

class FeeChangeUseCase {

	public function __construct(
		private readonly FeeChangeRepository $feeChangeRepository,
		private readonly PaymentServiceFactory $paymentServiceFactory,
		private readonly URLAuthenticator $urlAuthenticator
	) {
	}

	public function showFeeChange( string $uuid, ShowFeeChangePresenter $presenter ): void {
		if ( !$this->feeChangeRepository->feeChangeExists( $uuid ) ) {
			$presenter->showFeeChangeError();
			return;
		}

		$feeChange = $this->feeChangeRepository->getFeeChange( $uuid );

		if ( $feeChange->getState() !== FeeChangeState::NEW ) {
			$presenter->showFeeChangeAlreadyFilled();
			return;
		}

		$presenter->showFeeChangeForm(
			$uuid,
			$feeChange->getExternalMemberId(),
			$feeChange->getCurrentAmountInCents(),
			$feeChange->getSuggestedAmountInCents(),
			$feeChange->getCurrentInterval()
		);
	}

	public function changeFee( FeeChangeRequest $feeChangeRequest ): FeeChangeResponse {
		try {
			$feeChange = $this->getFeeChange( $feeChangeRequest->uuid );

			if ( $feeChange->getState() !== FeeChangeState::NEW ) {
				return new FeeChangeResponse( false, [ 'fee_change_already_submitted' => "This fee change ({$feeChangeRequest->uuid}) was already submitted" ] );
			}

			$paymentCreationRequest = $this->newPaymentCreationRequest( $feeChangeRequest, $feeChange, $this->urlAuthenticator );
			$paymentCreationResponse = $this->paymentServiceFactory->getCreatePaymentUseCase()->createPayment( $paymentCreationRequest );

			if ( $paymentCreationResponse instanceof FailureResponse ) {
				$paymentViolations = new ApplicationValidationResult(
					[ ApplicationValidationResult::SOURCE_PAYMENT => $paymentCreationResponse->errorMessage ]
				);
				return new FeeChangeResponse( false, $paymentViolations->getViolations() );
			}

			$feeChange->updateMembershipFee( $paymentCreationResponse->paymentId );
			$this->feeChangeRepository->storeFeeChange( $feeChange );
			return new FeeChangeResponse( true );
		} catch ( FeeChangeException $e ) {
			return new FeeChangeResponse( false, [ 'exception' => $e->getMessage() ] );
		}
	}

	private function getFeeChange( string $uuid ): FeeChange {
		return $this->feeChangeRepository->getFeeChange( $uuid );
	}

	private function newPaymentCreationRequest( FeeChangeRequest $request, FeeChange $feeChange, URLAuthenticator $urlAuthenticator ): PaymentCreationRequest {
		return PaymentCreationRequest::newFromParameters(
			new PaymentParameters(
				$request->amountInEuroCents,
				$feeChange->getCurrentInterval(),
				$request->paymentType,
				$request->iban,
				$request->bic
			),
			$this->paymentServiceFactory->newPaymentValidator( ApplicantType::PERSON_APPLICANT ),
			new DomainSpecificContext(
				itemId: $feeChange->getId() ?? 0,
				startTimeForRecurringPayment: null,
				invoiceId: $feeChange->getUuid(),
			),
			$urlAuthenticator
		);
	}
}
