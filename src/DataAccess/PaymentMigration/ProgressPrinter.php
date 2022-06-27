<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\MembershipContext\DataAccess\PaymentMigration;

class ProgressPrinter {
	private int $numMemberships;
	private int $counter;
	private float $startTime;
	private float $lastOutput;
	private float $updateIntervalInSeconds;
	private \DateTimeZone $timeZone;

	public function __construct( int $updateIntervalInMilliseconds = 500 ) {
		$this->startTime = $this->lastOutput = microtime( true );
		$this->updateIntervalInSeconds = $updateIntervalInMilliseconds / 1000;
		// Using the default TS won't help much in Docker containers, but at least we attempt to print it correctly
		$this->timeZone = new \DateTimeZone( date_default_timezone_get() );
	}

	public function initialize( int $numMemberships ): void {
		$this->numMemberships = $numMemberships;
		$this->counter = 0;
		$this->startTime = $this->lastOutput = microtime( true );
	}

	public function printProgress( int $membershipId ): void {
		$this->counter++;
		$now = microtime( true );
		if ( $now - $this->lastOutput < $this->updateIntervalInSeconds ) {
			return;
		}
		$elapsed = $now - $this->startTime;
		$timePerMembership = $elapsed / $this->counter;
		$membershipsToGo = $this->numMemberships - $this->counter;
		$estimatedFinishSeconds = intval( $membershipsToGo * $timePerMembership );
		$estimatedFinishTime = ( new \DateTimeImmutable( 'now', $this->timeZone ) )
			->modify( "+$estimatedFinishSeconds seconds" )
			->format( "H:i:s" );
		$membershipsPerSecond = $this->counter / $elapsed;
		printf(
			"\r%d memberships processed (%d per second), ETA %d seconds (%s). Last ID was %d",
			$this->counter,
			$membershipsPerSecond,
			$estimatedFinishSeconds,
			$estimatedFinishTime,
			$membershipId
		);
	}

}
