<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\UseCases\HandleSubscriptionSignupNotification;

use Psr\Log\LoggerInterface;
use WMDE\Fundraising\MembershipContext\Authorization\ApplicationAuthorizer;
use WMDE\Fundraising\MembershipContext\Domain\Model\MembershipApplication;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\ApplicationRepository;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\GetMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Domain\Repositories\StoreMembershipApplicationException;
use WMDE\Fundraising\MembershipContext\Infrastructure\TemplateMailerInterface;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalData;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\ResponseModel\PaypalNotificationResponse;

/**
 * @license GPL-2.0-or-later
 */
class HandleSubscriptionSignupNotificationUseCase {

	private $repository;
	private $authorizationService;
	private $mailer;
	private $logger;

	public function __construct( ApplicationRepository $repository, ApplicationAuthorizer $authorizationService,
		TemplateMailerInterface $mailer, LoggerInterface $logger ) {
		$this->repository = $repository;
		$this->authorizationService = $authorizationService;
		$this->mailer = $mailer;
		$this->logger = $logger;
	}

	public function handleNotification( SubscriptionSignupRequest $request ): PaypalNotificationResponse {
		try {
			$membershipApplication = $this->repository->getApplicationById( $request->getApplicationId() );
		} catch ( GetMembershipApplicationException $ex ) {
			return $this->createErrorResponse( $ex );
		}

		if ( $membershipApplication === null ) {
			return $this->createUnhandledResponse( 'specified data set could not be found' );
		}

		return $this->handleRequestForMembershipApplication( $request, $membershipApplication );
	}

	private function handleRequestForMembershipApplication(
		SubscriptionSignupRequest $request,
		MembershipApplication $application ): PaypalNotificationResponse {
		$paymentMethod = $application->getPayment()->getPaymentMethod();

		if ( !( $paymentMethod instanceof PayPalPayment ) ) {
			return $this->createUnhandledResponse( 'Trying to handle IPN for non-PayPal membership application' );
		}

		if ( !$this->authorizationService->canModifyApplication( $request->getApplicationId() ) ) {
			return $this->createUnhandledResponse( 'Wrong access code for membership application' );
		}

		try {
			$application->confirmSubscriptionCreated( $this->newPayPalDataFromRequest( $request ) );
		} catch ( \RuntimeException $ex ) {
			return $this->createErrorResponse( $ex );
		}

		try {
			$this->repository->storeApplication( $application );
		}
		catch ( StoreMembershipApplicationException $ex ) {
			return $this->createErrorResponse( $ex );
		}

		$this->sendConfirmationEmail( $application );

		return PaypalNotificationResponse::newSuccessResponse();
	}

	private function createUnhandledResponse( string $reason ): PaypalNotificationResponse {
		return PaypalNotificationResponse::newUnhandledResponse( [
			'message' => $reason
		] );
	}

	private function createErrorResponse( \Exception $ex ): PaypalNotificationResponse {
		return PaypalNotificationResponse::newFailureResponse( [
			'message' => $ex->getMessage(),
			'stackTrace' => $ex->getTraceAsString()
		] );
	}

	private function sendConfirmationEmail( MembershipApplication $application ): void {
		try {
			$this->mailer->sendMail(
				$application->getApplicant()->getEmailAddress(),
				[
					'membershipType' => $application->getType(),
					'membershipFee' => $application->getPayment()->getAmount()->getEuroString(),
					'paymentIntervalInMonths' => $application->getPayment()->getIntervalInMonths(),
					'paymentType' => $application->getPayment()->getPaymentMethod()->getId(),
					'salutation' => $application->getApplicant()->getName()->getSalutation(),
					'title' => $application->getApplicant()->getName()->getTitle(),
					'lastName' => $application->getApplicant()->getName()->getLastName(),
					'firstName' => $application->getApplicant()->getName()->getFirstName(),
				]
			);
		} catch ( \RuntimeException $ex ) {
			// no need to re-throw or return false, this is not a fatal error, only a minor inconvenience
		}
	}

	private function newPayPalDataFromRequest( SubscriptionSignupRequest $request ): PayPalData {
		return ( new PayPalData() )
			->setPayerId( $request->getPayerId() )
			->setSubscriberId( $request->getSubscriptionId() )
			->setPayerStatus( $request->getPayerStatus() )
			->setAddressStatus( $request->getPayerAddressStatus() )
			->setFirstName( $request->getPayerFirstName() )
			->setLastName( $request->getPayerLastName() )
			->setAddressName( $request->getPayerAddressName() )
			->setPaymentType( $request->getPaymentType() )
			->setPaymentStatus( implode( '/', [ $request->getPaymentType(), $request->getTransactionType() ] ) )
			->setCurrencyCode( $request->getCurrencyCode() );
	}

}
