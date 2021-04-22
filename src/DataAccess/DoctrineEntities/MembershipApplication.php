<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\DoctrineEntities;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use WMDE\Fundraising\MembershipContext\DataAccess\MembershipApplicationData;
use WMDE\Fundraising\MembershipContext\Domain\Model\Incentive;

/**
 * @since 2.0
 *
 * @ORM\Table(
 *     name="request",
 *     indexes={
 *			@ORM\Index(name="m_email", columns={"email"}, flags={"fulltext"}),
 *          @ORM\Index(name="m_name", columns={"name"}, flags={"fulltext"}),
 *     		@ORM\Index(name="m_ort", columns={"ort"}, flags={"fulltext"})
 *     }
 *	 )
 * @ORM\Entity
 */
class MembershipApplication {

	public const STATUS_CONFIRMED = 1;
	public const STATUS_NEUTRAL = 0;
	public const STATUS_CANCELED = -1;
	public const STATUS_MODERATION = -2;
	public const STATUS_CANCELLED_MODERATION = -3;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="IDENTITY")
	 */
	private $id;

	/**
	 * FIXME: this should not be nullable
	 *
	 * @var int
	 *
	 * @ORM\Column(name="status", type="smallint", options={"default":0}, nullable=true)
	 */
	private $status = 0;

	/**
	 * This is no longer written by the fundraising frontend.
	 *
	 * Until we remove all references to this field in the backend, the field is not removed but just marked as deprecated.
	 *
	 * @deprecated
	 * @var integer|null
	 *
	 * @ORM\Column(name="donation_id", type="integer", nullable=true)
	 */
	private $donationId;

	/**
	 * @var DateTime
	 *
	 * @Gedmo\Timestampable(on="create")
	 * @ORM\Column(name="timestamp", type="datetime")
	 */
	private $creationTime;

	/**
	 * @var string|null
	 *
	 * @ORM\Column(name="anrede", type="string", length=16, nullable=true)
	 */
	private $applicantSalutation;

	/**
	 * @var string|null
	 *
	 * @ORM\Column(name="firma", type="string", length=100, nullable=true)
	 */
	private $company;

	/**
	 * @var string|null
	 *
	 * @ORM\Column(name="titel", type="string", length=16, nullable=true)
	 */
	private $applicantTitle;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="name", type="string", length=250, options={"default":""}, nullable=false)
	 */
	private $probablyUnusedNameField = '';

	/**
	 * @var string
	 *
	 * @ORM\Column(name="vorname", type="string", length=50, options={"default":""}, nullable=false)
	 */
	private $applicantFirstName = '';

	/**
	 * @var string
	 *
	 * @ORM\Column(name="nachname", type="string", length=50, options={"default":""}, nullable=false)
	 */
	private $applicantLastName = '';

	/**
	 * @var string|null
	 *
	 * @ORM\Column(name="strasse", type="string", length=100, nullable=true)
	 */
	private $address;

	/**
	 * @var string|null
	 *
	 * @ORM\Column(name="plz", type="string", length=8, nullable=true)
	 */
	private $postcode;

	/**
	 * @var string|null
	 *
	 * @ORM\Column(name="ort", type="string", length=100, nullable=true)
	 */
	private $city;

	/**
	 * @var string|null
	 *
	 * @ORM\Column(name="country", type="string", length=8, options={"default":""}, nullable=true)
	 */
	private $country = '';

	/**
	 * @var string
	 *
	 * @ORM\Column(name="email", type="string", length=250, options={"default":""}, nullable=false)
	 */
	private $applicantEmailAddress = '';

	/**
	 * @var string
	 *
	 * @ORM\Column(name="phone", type="string", length=30, options={"default":""}, nullable=false)
	 */
	private $applicantPhoneNumber = '';

	/**
	 * Date of birth
	 *
	 * @var DateTime|null
	 *
	 * @ORM\Column(name="dob", type="date", nullable=true)
	 */
	private $applicantDateOfBirth;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="wikimedium_shipping", type="string", options={"default":""}, nullable=false)
	 */
	private $wikimediumShipping = 'none';

	/**
	 * @var string
	 *
	 * @ORM\Column(name="membership_type", type="string", options={"default":"sustaining"}, nullable=false)
	 */
	private $membershipType = 'sustaining';

	/**
	 * @var string
	 *
	 * @ORM\Column(name="payment_type", type="string", options={"default":"BEZ"}, nullable=false)
	 */
	private $paymentType = 'BEZ';

	/**
	 * @var int
	 *
	 * @ORM\Column(name="membership_fee", type="integer", options={"default":0}, nullable=false)
	 */
	private $paymentAmountInEuro = 0;

	/**
	 * FIXME: this should not be nullable
	 *
	 * @var int
	 *
	 * @ORM\Column(name="membership_fee_interval", type="smallint", options={"default":12}, nullable=true)
	 */
	private $paymentIntervalInMonths = 12;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="account_number", type="string", length=16, options={"default":""}, nullable=false)
	 */
	private $paymentBankAccount = '';

	/**
	 * @var string
	 *
	 * @ORM\Column(name="bank_name", type="string", length=100, options={"default":""}, nullable=false)
	 */
	private $paymentBankName = '';

	/**
	 * @var string
	 *
	 * @ORM\Column(name="bank_code", type="string", length=16, options={"default":""}, nullable=false)
	 */
	private $paymentBankCode = '';

	/**
	 * @var string|null
	 *
	 * @ORM\Column(name="iban", type="string", length=32, options={"default":""}, nullable=true)
	 */
	private $paymentIban = '';

	/**
	 * @var string|null
	 *
	 * @ORM\Column(name="bic", type="string", length=32, options={"default":""}, nullable=true)
	 */
	private $paymentBic = '';

	/**
	 * @var string
	 *
	 * @ORM\Column(name="account_holder", type="string", length=50, options={"default":""}, nullable=false)
	 */
	private $paymentBankAccountHolder = '';

	/**
	 * @var string
	 *
	 * @ORM\Column(name="comment", type="text", options={"default":""}, nullable=false)
	 */
	private $comment = '';

	/**
	 * @var DateTime|null
	 *
	 * @ORM\Column(name="export", type="datetime", nullable=true)
	 */
	private $export;

	/**
	 * @var DateTime|null
	 *
	 * @ORM\Column(name="backup", type="datetime", nullable=true)
	 */
	private $backup;

	/**
	 * @var bool
	 *
	 * @ORM\Column(name="wikilogin", type="boolean", options={"default":0}, nullable=false)
	 */
	private $wikilogin = false;

	/**
	 * @var string|null
	 *
	 * @ORM\Column(name="tracking", type="string", length=50, nullable=true)
	 */
	private $tracking;

	/**
	 * @var string|null
	 *
	 * @ORM\Column(name="data", type="text", nullable=true)
	 */
	private $data;

	/**
	 * @var bool|null
	 *
	 * @ORM\Column(name="donation_receipt", type="boolean", nullable=true)
	 */
	private $donationReceipt;

	/**
	 * @var Collection<Incentive>
	 *
	 * @ORM\ManyToMany(targetEntity="WMDE\Fundraising\MembershipContext\Domain\Model\Incentive")
	 * @ORM\JoinTable(name="membership_incentive",
	 *      joinColumns={@ORM\JoinColumn(name="membership_id", referencedColumnName="id")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="incentive_id", referencedColumnName="id")}
	 *      )
	 */
	private $incentives;

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

	public function setWikimediumShipping( string $wikimediumShipping ): self {
		$this->wikimediumShipping = $wikimediumShipping;

		return $this;
	}

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

	public function setPaymentType( string $paymentType ): self {
		$this->paymentType = $paymentType;

		return $this;
	}

	public function getPaymentType(): string {
		return $this->paymentType;
	}

	public function setPaymentAmount( int $paymentAmountInEuro ): self {
		$this->paymentAmountInEuro = $paymentAmountInEuro;

		return $this;
	}

	public function getPaymentAmount(): int {
		return $this->paymentAmountInEuro;
	}

	public function setPaymentIntervalInMonths( int $paymentIntervalInMonths ): self {
		$this->paymentIntervalInMonths = $paymentIntervalInMonths;

		return $this;
	}

	public function getPaymentIntervalInMonths(): int {
		return $this->paymentIntervalInMonths;
	}

	public function setPaymentBankAccount( string $paymentBankAccount ): self {
		$this->paymentBankAccount = $paymentBankAccount;

		return $this;
	}

	public function getPaymentBankAccount(): string {
		return $this->paymentBankAccount;
	}

	public function setPaymentBankName( string $paymentBankName ): self {
		$this->paymentBankName = $paymentBankName;

		return $this;
	}

	public function getPaymentBankName(): string {
		return $this->paymentBankName;
	}

	public function setPaymentBankCode( string $paymentBankCode ): self {
		$this->paymentBankCode = $paymentBankCode;

		return $this;
	}

	public function getPaymentBankCode(): string {
		return $this->paymentBankCode;
	}

	public function setPaymentIban( ?string $paymentIban ): self {
		$this->paymentIban = $paymentIban;

		return $this;
	}

	public function getPaymentIban(): ?string {
		return $this->paymentIban;
	}

	public function setPaymentBic( ?string $paymentBic ): self {
		$this->paymentBic = $paymentBic;

		return $this;
	}

	public function getPaymentBic(): ?string {
		return $this->paymentBic;
	}

	public function setPaymentBankAccountHolder( string $paymentBankAccountHolder ): self {
		$this->paymentBankAccountHolder = $paymentBankAccountHolder;

		return $this;
	}

	public function getPaymentBankAccountHolder(): string {
		return $this->paymentBankAccountHolder;
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

	public function setCountry( ?string $country ): self {
		$this->country = $country;

		return $this;
	}

	public function getCountry(): ?string {
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
		// TODO: I think this logic is wrong, does having any status make an application confirmed?
		return $this->status !== self::STATUS_NEUTRAL;
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
}
