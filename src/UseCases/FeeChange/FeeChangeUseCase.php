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

	/**
	 * A dummy payment ID to indicate failure
	 */
	private const int FAILED_PAYMENT_ID = -1;

	public function __construct(
		private readonly FeeChangeRepository $feeChangeRepository,
		private readonly PaymentServiceFactory $paymentServiceFactory,
		private readonly URLAuthenticator $urlAuthenticator,
		private readonly bool $isActive
	) {
	}

	public function showFeeChange( string $uuid, ShowFeeChangePresenter $presenter ): void {
		if ( !$this->isActive ) {
			$presenter->showFeeChangeInactive();
			return;
		}

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
			if ( !$this->isActive ) {
				return new FeeChangeResponse( false, [ 'fee_change_inactive' => "This fee change ({$feeChangeRequest->uuid}) could not be changed because the fee change is inavtive" ] );
			}

			$feeChange = $this->getFeeChange( $feeChangeRequest->uuid );

			if ( $feeChange->getState() !== FeeChangeState::NEW ) {
				return new FeeChangeResponse( false, [ 'fee_change_already_submitted' => "This fee change ({$feeChangeRequest->uuid}) was already submitted" ] );
			}

			$errors = [];

			if ( $feeChangeRequest->memberName == "" ) {
				$errors[ 'member_name_required' ] = 'Member name is required';
			}

			$paymentCreationRequest = $this->newPaymentCreationRequest( $feeChangeRequest, $feeChange, $this->urlAuthenticator );
			$paymentCreationResponse = $this->paymentServiceFactory->getCreatePaymentUseCase()->createPayment( $paymentCreationRequest );

			$paymentId = self::FAILED_PAYMENT_ID;
			if ( $paymentCreationResponse instanceof FailureResponse ) {
				$errors = array_merge(
					$errors,
					[ ApplicationValidationResult::SOURCE_PAYMENT => $paymentCreationResponse->errorMessage ]
				);
			} else {
				$paymentId = $paymentCreationResponse->paymentId;
			}

			if ( count( $errors ) > 0 || $paymentId === self::FAILED_PAYMENT_ID ) {
				return new FeeChangeResponse( false, $errors );
			}

			$feeChange->updateMembershipFee( $paymentId, $feeChangeRequest->memberName );
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
