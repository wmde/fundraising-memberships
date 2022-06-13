<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use WMDE\Fundraising\MembershipContext\DataAccess\MembershipApplicationData;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;

class MembershipApplication {

	public const STATUS_CONFIRMED = 1;
	public const STATUS_NEUTRAL = 0;
	public const STATUS_CANCELED = -1;
	public const STATUS_MODERATION = -2;
	public const STATUS_CANCELLED_MODERATION = -3;

	private ?int $id = null;

	/**
	 * FIXME: this should not be nullable
	 *
	 * @var int
	 *
	 */
	private int $status = 0;

	/**
	 * This is no longer written by the fundraising frontend.
	 *
	 * Until we remove all references to this field in the backend, the field is not removed but just marked as deprecated.
	 *
	 * @deprecated
	 */
	private ?int $donationId = null;

	private ?DateTime $creationTime = null;

	private ?string $applicantSalutation = null;

	private ?string $company = null;

	private ?string $applicantTitle = null;

	/**
	 * @var string field is used in the FOC and some exports, see https://phabricator.wikimedia.org/T308878
	 */
	private string $probablyUnusedNameField = '';

	private string $applicantFirstName = '';

	private string $applicantLastName = '';

	private ?string $address = null;

	private ?string $postcode = null;

	private ?string $city = null;

	private string $country = '';

	private string $applicantEmailAddress = '';

	private string $applicantPhoneNumber = '';

	private ?DateTime $applicantDateOfBirth = null;

	/**
	 * Wikimedium used to be a membership magazine that a user could subscribe to. Not offered anymore.
	 * @deprecated
	 */
	private string $wikimediumShipping = 'none';

	private string $membershipType = 'sustaining';

	/**
	 * @deprecated
	 */
	private string $paymentType = 'BEZ';

	/**
	 * @deprecated
	 */
	private int $paymentAmountInEuro = 0;

	/**
	 * FIXME: this should not be nullable
	 *
	 * @var int
	 * @deprecated
	 */
	private int $paymentIntervalInMonths = 12;

	/**
	 * @deprecated
	 */
	private string $paymentBankAccount = '';

	/**
	 * @deprecated
	 */
	private string $paymentBankName = '';

	/**
	 * @deprecated
	 */
	private string $paymentBankCode = '';

	/**
	 * @deprecated
	 */
	private string $paymentIban = '';

	/**
	 * @deprecated
	 */
	private string $paymentBic = '';

	private string $comment = '';

	private ?DateTime $export = null;

	private ?DateTime $backup = null;

	private bool $wikilogin = false;

	private ?string $tracking = null;

	private ?string $data = null;

	private ?bool $donationReceipt = null;

	private Collection|ArrayCollection $incentives;

	private int $paymentId;

	public function __construct() {
		$this->incentives = new ArrayCollection();
	}

	public function getId(): ?int {
		return $this->id;
	}

	public function setId( ?int $id ) {
		$this->id = $id;
	}

	public function setDonationId( ?int $donationId ): self {
		$this->donationId = $donationId;

		return $this;
	}

	/**
	 * Returns the id of the donation that led to the membership application,
	 * or null when the application is not linked to any donation.
	 *
	 * @return int|null
	 */
	public function getDonationId(): ?int {
		return $this->donationId;
	}

	public function setCreationTime( ?DateTime $creationTime ): self {
		$this->creationTime = $creationTime;

		return $this;
	}

	public function getCreationTime(): ?DateTime {
		return $this->creationTime;
	}

	public function setApplicantSalutation( ?string $applicantSalutation ): self {
		$this->applicantSalutation = $applicantSalutation;

		return $this;
	}

	public function getApplicantSalutation(): ?string {
		return $this->applicantSalutation;
	}

	public function setCompany( ?string $company ): self {
		$this->company = $company;

		return $this;
	}

	public function getCompany(): ?string {
		return $this->company;
	}

	public function setApplicantTitle( string $applicantTitle ): self {
		$this->applicantTitle = $applicantTitle;

		return $this;
	}

	public function getApplicantTitle(): string {
		return $this->applicantTitle;
	}

	public function setProbablyUnusedNameField( string $probablyUnusedNameField ): self {
		$this->probablyUnusedNameField = $probablyUnusedNameField;

		return $this;
	}

	public function getProbablyUnusedNameField(): string {
		return $this->probablyUnusedNameField;
	}

	public function setApplicantFirstName( string $applicantFirstName ): self {
		$this->applicantFirstName = $applicantFirstName;
		$this->setNameFromParts( $applicantFirstName, $this->getApplicantLastName() );

		return $this;
	}

	public function getApplicantFirstName(): string {
		return $this->applicantFirstName;
	}

	public function setApplicantLastName( string $applicantLastName ): self {
		$this->applicantLastName = $applicantLastName;
		$this->setNameFromParts( $this->getApplicantFirstName(), $applicantLastName );

		return $this;
	}

	public function getApplicantLastName(): string {
		return $this->applicantLastName;
	}

	private function setNameFromParts( ?string $firstName, ?string $lastName ): self {
		$this->setProbablyUnusedNameField( implode(
			' ',
			array_filter( [ $firstName, $lastName ] )
		) );

		return $this;
	}

	public function setAddress( ?string $address ): self {
		$this->address = $address;

		return $this;
	}

	public function getAddress(): ?string {
		return $this->address;
	}

	public function setPostcode( ?string $postcode ): self {
		$this->postcode = $postcode;

		return $this;
	}

	public function getPostcode(): ?string {
		return $this->postcode;
	}

	public function setCity( ?string $city ): self {
		$this->city = $city;

		return $this;
	}

	public function getCity(): ?string {
		return $this->city;
	}

	/**
	 * Set email
	 *
	 * @param string $applicantEmailAddress
	 *
	 * @return self
	 */
	public function setApplicantEmailAddress( string $applicantEmailAddress ): self {
		$this->applicantEmailAddress = $applicantEmailAddress;

		return $this;
	}

	/**
	 * Get email
	 *
	 * @return string
	 */
	public function getApplicantEmailAddress(): string {
		return $this->applicantEmailAddress;
	}

	/**
	 * Set phone
	 *
	 * @param string $applicantPhoneNumber
	 *
	 * @return self
	 */
	public function setApplicantPhoneNumber( string $applicantPhoneNumber ): self {
		$this->applicantPhoneNumber = $applicantPhoneNumber;

		return $this;
	}

	/**
	 * Get phone
	 *
	 * @return string
	 */
	public function getApplicantPhoneNumber(): string {
		return $this->applicantPhoneNumber;
	}

	public function setApplicantDateOfBirth( ?DateTime $dateOfBirth ): self {
		$this->applicantDateOfBirth = $dateOfBirth;

		return $this;
	}

	public function getApplicantDateOfBirth(): ?DateTime {
		return $this->applicantDateOfBirth;
	}

	/**
	 * @param string $wikimediumShipping
	 *
	 * @return $this
	 */
	public function setWikimediumShipping( string $wikimediumShipping ): self {
		$this->wikimediumShipping = $wikimediumShipping;

		return $this;
	}

	/**
	 * @return string
	 * @deprecated
	 */
	public function getWikimediumShipping(): string {
		return $this->wikimediumShipping;
	}

	public function setMembershipType( string $membershipType ): self {
		$this->membershipType = $membershipType;

		return $this;
	}

	public function getMembershipType(): string {
		return $this->membershipType;
	}

	/**
	 * @deprecated
	 */
	public function setPaymentType( string $paymentType ): self {
		$this->paymentType = $paymentType;

		return $this;
	}

	/**
	 * @deprecated
	 */
	public function getPaymentType(): string {
		return $this->paymentType;
	}

	/**
	 * @deprecated
	 */
	public function setPaymentAmount( int $paymentAmountInEuro ): self {
		$this->paymentAmountInEuro = $paymentAmountInEuro;

		return $this;
	}

	/**
	 * @deprecated
	 */
	public function getPaymentAmount(): int {
		return $this->paymentAmountInEuro;
	}

	/**
	 * @deprecated
	 */
	public function setPaymentIntervalInMonths( int $paymentIntervalInMonths ): self {
		$this->paymentIntervalInMonths = $paymentIntervalInMonths;

		return $this;
	}

	/**
	 * @deprecated
	 */
	public function getPaymentIntervalInMonths(): int {
		return $this->paymentIntervalInMonths;
	}

	/**
	 * @deprecated
	 */
	public function setPaymentBankAccount( string $paymentBankAccount ): self {
		$this->paymentBankAccount = $paymentBankAccount;

		return $this;
	}

	/**
	 * @deprecated
	 */
	public function getPaymentBankAccount(): string {
		return $this->paymentBankAccount;
	}

	/**
	 * @deprecated
	 */
	public function setPaymentBankName( string $paymentBankName ): self {
		$this->paymentBankName = $paymentBankName;

		return $this;
	}

	/**
	 * @deprecated
	 */
	public function getPaymentBankName(): string {
		return $this->paymentBankName;
	}

	/**
	 * @deprecated
	 */
	public function setPaymentBankCode( string $paymentBankCode ): self {
		$this->paymentBankCode = $paymentBankCode;

		return $this;
	}

	/**
	 * @deprecated
	 */
	public function getPaymentBankCode(): string {
		return $this->paymentBankCode;
	}

	/**
	 * @deprecated
	 */
	public function setPaymentIban( string $paymentIban ): self {
		$this->paymentIban = $paymentIban;

		return $this;
	}

	/**
	 * @deprecated
	 */
	public function getPaymentIban(): string {
		return $this->paymentIban;
	}

	/**
	 * @deprecated
	 */
	public function setPaymentBic( string $paymentBic ): self {
		$this->paymentBic = $paymentBic;

		return $this;
	}

	/**
	 * @deprecated
	 */
	public function getPaymentBic(): string {
		return $this->paymentBic;
	}

	public function setComment( string $comment ): self {
		$this->comment = $comment;

		return $this;
	}

	public function getComment(): string {
		return $this->comment;
	}

	/**
	 * Sets the time of export.
	 *
	 * @param DateTime|null $export
	 *
	 * @return self
	 */
	public function setExport( ?DateTime $export ): self {
		$this->export = $export;

		return $this;
	}

	/**
	 * Returns the time of export.
	 *
	 * @return DateTime|null
	 */
	public function getExport(): ?DateTime {
		return $this->export;
	}

	/**
	 * Sets the time of backup.
	 *
	 * @param DateTime|null $backup
	 *
	 * @return self
	 */
	public function setBackup( ?DateTime $backup ): self {
		$this->backup = $backup;

		return $this;
	}

	/**
	 * Returns the time of backup.
	 *
	 * @return DateTime|null
	 */
	public function getBackup(): ?DateTime {
		return $this->backup;
	}

	public function setWikilogin( bool $wikilogin ): self {
		$this->wikilogin = $wikilogin;

		return $this;
	}

	public function getWikilogin(): bool {
		return $this->wikilogin;
	}

	public function setTracking( ?string $tracking ): self {
		$this->tracking = $tracking;

		return $this;
	}

	public function getTracking(): ?string {
		return $this->tracking;
	}

	/**
	 * Sets the status of the membership request.
	 * The allowed values are the STATUS_ constants in this class.
	 *
	 * @param int $status
	 *
	 * @return self
	 */
	public function setStatus( int $status ): self {
		$this->status = $status;

		return $this;
	}

	/**
	 * Returns the status of the membership request.
	 * The possible values are the STATUS_ constants in this class.
	 *
	 * @return int
	 */
	public function getStatus(): int {
		return $this->status;
	}

	public function setCountry( string $country ): self {
		$this->country = $country;

		return $this;
	}

	public function getCountry(): string {
		return $this->country;
	}

	public function setData( ?string $data ): self {
		$this->data = $data;

		return $this;
	}

	public function getData(): ?string {
		return $this->data;
	}

	public function isConfirmed(): bool {
		return $this->status === self::STATUS_CONFIRMED;
	}

	/**
	 * @deprecated This will be removed when the bitwise statuses are properly refactored out
	 */
	public function needsModeration(): bool {
		if ( $this->status >= 0 ) {
			return false;
		}

		return in_array( $this->status, [ self::STATUS_MODERATION, self::STATUS_CANCELLED_MODERATION ] );
	}

	/**
	 * @deprecated This will be removed when the bitwise statuses are properly refactored out
	 */
	public function isCancelled(): bool {
		if ( $this->status >= 0 ) {
			return false;
		}

		return in_array( $this->status, [ self::STATUS_CANCELED, self::STATUS_CANCELLED_MODERATION ] );
	}

	public function log( string $message ): self {
		$dataArray = $this->getDecodedData();
		$dataArray['log'][date( 'Y-m-d H:i:s' )] = $message;
		$this->encodeAndSetData( $dataArray );

		return $this;
	}

	/**
	 * NOTE: if possible, use @see getDataObject instead, as it provides a nicer API.
	 *
	 * @return array
	 */
	public function getDecodedData(): array {
		if ( $this->data === null ) {
			return [];
		}

		$data = unserialize( base64_decode( $this->data ) );

		return is_array( $data ) ? $data : [];
	}

	/**
	 * NOTE: if possible, use @see modifyDataObject instead, as it provides a nicer API.
	 *
	 * @param array $dataArray
	 */
	public function encodeAndSetData( array $dataArray ) {
		$this->data = base64_encode( serialize( $dataArray ) );
	}

	/**
	 * WARNING: updates made to the return value will not be reflected in the Donation state.
	 * Similarly, updates to the Donation state will not propagate to the returned object.
	 * To update the Donation state, explicitly call @see setDataObject.
	 *
	 * @return MembershipApplicationData
	 */
	public function getDataObject(): MembershipApplicationData {
		$dataArray = $this->getDecodedData();

		$data = new MembershipApplicationData();

		$data->setAccessToken( array_key_exists( 'token', $dataArray ) ? $dataArray['token'] : null );
		$data->setUpdateToken( array_key_exists( 'utoken', $dataArray ) ? $dataArray['utoken'] : null );
		$data->setPreservedStatus( array_key_exists( 'old_status', $dataArray ) ? $dataArray['old_status'] : null );

		return $data;
	}

	public function setDataObject( MembershipApplicationData $data ): void {
		$dataArray = array_merge(
			$this->getDecodedData(),
			[
				'token' => $data->getAccessToken(),
				'utoken' => $data->getUpdateToken(),
				'old_status' => $data->getPreservedStatus(),
			]
		);

		foreach ( [ 'token', 'utoken', 'old_status' ] as $keyName ) {
			if ( $dataArray[$keyName] === null ) {
				unset( $dataArray[$keyName] );
			}
		}

		$this->encodeAndSetData( $dataArray );
	}

	/**
	 * @param callable $modificationFunction Takes a modifiable MembershipApplicationData parameter
	 */
	public function modifyDataObject( callable $modificationFunction ) {
		$dataObject = $this->getDataObject();
		$modificationFunction( $dataObject );
		$this->setDataObject( $dataObject );
	}

	/**
	 * Set donation receipt state
	 *
	 * @param bool|null $donationReceipt
	 * @return self
	 */
	public function setDonationReceipt( ?bool $donationReceipt ): self {
		$this->donationReceipt = $donationReceipt;

		return $this;
	}

	/**
	 * Get donation receipt state
	 *
	 * @return bool|null
	 */
	public function getDonationReceipt(): ?bool {
		return $this->donationReceipt;
	}

	public function getIncentives(): Collection {
		return $this->incentives;
	}

	/**
	 * @param Collection<Incentive> $incentives
	 * @return self
	 */
	public function setIncentives( Collection $incentives ): self {
		$this->incentives = $incentives;
		return $this;
	}

	public function getPaymentId(): int {
		return $this->paymentId;
	}

	public function setPaymentId( int $paymentId ): void {
		$this->paymentId = $paymentId;
	}
}
